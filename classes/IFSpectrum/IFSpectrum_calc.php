<?php

/**
 * ALMA - Atacama Large Millimeter Array
 * (c) Associated Universities Inc., 2014
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307  USA
 *
 * @author Morgan McLeod
 * Version 2.0 MTM 10/31/2014   Remake of old pwrSpecTools class.
 *
 */

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.logger.php');

// define('DEBUG_IFSPECTRUM_CALC', true);

class IFSpectrum_calc {
    private $data;                  // same structure as for setData()
    private $noiseFloorData;
    private $cablePad;              // dB of pad in cable to compensate for.
    private $maxVarWindow;          // maximum variation seen in any call to getPowerVarWindow();
    private $badVarLOs;             // array of LO freqs where power var crossed spec line.
    private $badVarThisRange;       // True= most recently processed power var range crossed spec line.

    private $rfMin;                 // Frequency limits of the RF band.
    private $rfMax;                 //

    private $logger1;               // Logger for debug output

    private $minMaxData;            // Min and max power, etc. per LO:
    // array(
    //     'LO_GHz'   => float,
    //     'pMin_dBm' => float,
    //     'pMax_dBm' => float,
    //     'last_dBm' => float    // rightmost point in trace, for Y2 axis labels.
    // )

    private $pMin_dBm_overall;      // Min and max power overall.
    private $pMax_dBm_overall;      //


    const BAD_LO = -999;          // GHz  Invalid value for LO
    const HUGE_POWER = 999;       // dBm  Invalid big value for power
    const TINY_POWER = -999;      // dBm  Invalid small value for power
    const SMALL_MW = 1.0e-9;      // mW  Minimum result to return when subtracting noise floor.
    const MIN_IF_BIN = 0.003;     // GHz  We assume the analyzer bins are 3 MHz.
    const LOW_IF_CUTOFF = 0.010;  // Ghz  Exlude power from below 10 MHz from total power calc.
    const DFLT_CABLEPAD = 6.0;    // dB   Assumed cable pad to compensate for.
    const USB = 1;                // sideband indicator for RF range limiting
    const LSB = 2;                // sideband indicator for RF range limiting
    const RFMIN_DEFAULT = 0.0;      // default value for rfMin
    const RFMAX_DEFAULT = 99999.9;  // default value for rfMax

    /**
     * Constructor
     *
     * @param $data structure shown for setData() below.
     */
    public function __construct($data = false) {
        $this->setData($data);
        $this->cablePad = self::DFLT_CABLEPAD;
        unset($this->noiseFloorData);
        $this->noiseFloorData = array();
        $rfMin = self::RFMIN_DEFAULT;
        $rfMax = self::RFMAX_DEFAULT;

        if (defined('DEBUG_IFSPECTRUM_CALC'))
            $this->logger1 = new Logger("IFSpectrum_calc.txt");
    }

    /**
     * Asssign new data.
     *
     * @param $sortData if true sort data after insert.
     * @param $data structure is:
     * array(
     *     [0] => Array(
     *          'LO_GHz' => float,    // LO frequency
     *          'Freq_GHz' => float,  // Spectrum analyzer IF center
     *          'Power_dBm' => float  // Spectrum analyzer power measurement
     *     )
     *     [1] => Array...
     * )
     */
    public function setData($data, $sortData = false) {
        unset($this->data);
        if ($data)
            $this->data = $data;
        else
            $this->data = array();
        if ($sortData)
            $this->sortData();
        $this->maxVarWindow = 0;
    }

    public function setRFLimits($rfMin = self::RFMIN_DEFAULT, $rfMax = self::RFMAX_DEFAULT) {
        $this->rfMin = $rfMin;
        $this->rfMax = $rfMax;
    }

    public function getMaxVarWindow() {
        return $this->maxVarWindow;
    }

    public function getBadVarLOs() {
        return $this->badVarLOs;
    }

    /**
     * Assign noise floor trace to use for correcting data traces.
     *
     * @param $data array must have the same number of points as the per-LO traces.  Structure is:
     * array(
     *     [0] => Array(
     *          'Freq_GHz' => float,   // Spectrum analyzer IF center
     *          'Power_dBm' => float  // Spectrum analyzer power measurement
     *     )
     *     [1] => Array...
     * )
     */
    public function setNoiseFloorData($nfData) {
        $this->noiseFloorData = $nfData;
    }

    /**
     * Sort the data ascending by LO_GHz, Freq_GHz.
     */
    private function sortData() {
        usort($this->data, function (array $a, array $b) {
            if ($a['LO_GHz'] != $b['LO_GHz'])
                return $a['LO_GHz'] - $b['LO_GHz'];
            else
                return $a['Freq_GHz'] - $b['Freq_GHz'];
        });
    }

    /**
     * Get an array of distinct LOs in the data set.
     */
    public function getLOs() {
        if (empty($this->data))
            return false;

        $output = array();
        $lastLO = self::BAD_LO;

        // loop on all rows:
        foreach ($this->data as $row) {
            $LO = $row['LO_GHz'];
            // for each new LO seen,
            if ($LO != $lastLO) {
                $lastLO = $LO;
                // append to output array:
                $output[] = $LO;
            }
        }
        return $output;
    }

    /**
     * Get an array giving the start and stop indexes for each LO in the data set.
     *
     * @return array(
     *     [0] => Array(
     *         'LO_GHz' => float,        // An LO frequency
     *         'minIndex' => integer,    // The minimum index in $this->data pertaining to the LO.
     *         'maxIndex' => integer     // The maximum index.
     *     )
     *     [1] => Array...
     * )
     */
    private function getLORanges() {
        if (empty($this->data))
            return false;

        $output = array();
        $outputRec = array(
            'LO_GHz' => self::BAD_LO,
            'minIndex' => 0,
            'maxIndex' => 0
        );

        // loop on all rows:
        $index = 0;
        foreach ($this->data as $row) {
            $row = $this->data[$index];
            $LO = $row['LO_GHz'];
            // for each new LO seen:
            if ($LO != $outputRec['LO_GHz']) {
                // if the previous LO seen is not our start token:
                if ($outputRec['LO_GHz'] != self::BAD_LO) {
                    // append the record to the output array:
                    $output[] = $outputRec;
                }
                // Save the new LO and its min index:
                $outputRec['LO_GHz'] = $LO;
                $outputRec['minIndex'] = $index;
            }
            // Same LO as previously so increas the max index:
            $outputRec['maxIndex'] = $index;
            // increment for the next iteration:
            $index++;
        }
        // append the final record:
        $output[] = $outputRec;
        return $output;
    }

    /**
     * Accumulates min, max, and highest-IF power levels seen per LO.
     *
     * @return none.   Updates internal arrays and accumulators.
     */
    public function getTotalPowerSpans() {
        // helper function to append a record to the output:
        $appendResult = function (&$output, &$pMin_overall, &$pMax_overall, $LO, $mindBm, $maxdBm, $lastdBm) {
            $output[] = array(
                'LO_GHz' => $LO,
                'pMin_dBm' => $mindBm,
                'pMax_dBm' => $maxdBm,
                'last_dBm' => $lastdBm
            );
            // and accumulate overall min/max:
            if ($mindBm < $pMin_overall)
                $pMin_overall = $mindBm;
            if ($maxdBm > $pMax_overall)
                $pMax_overall = $maxdBm;
        };

        // reset the min/max and trace offset accumulators:
        $this->minMaxData = array();
        $this->pMin_dBm_overall = self::HUGE_POWER;
        $this->pMax_dBm_overall = self::TINY_POWER;

        if (empty($this->data))
            return array();

        // to accumulate min/max powers seen per LO:
        $mindBm = self::HUGE_POWER;
        $maxdBm = self::TINY_POWER;
        $lastdBm = self::TINY_POWER;
        $lastLO = self::BAD_LO;

        // Loop for all rows:
        foreach ($this->data as $row) {
            $LO = $row['LO_GHz'];
            $IF = $row['Freq_GHz'];
            $PW = $row['Power_dBm'];

            // if this is the first row, initialize lastLO:
            if ($lastLO == self::BAD_LO)
                $lastLO = $LO;

            // if same as last LO or first row:
            if ($LO == $lastLO) {
                // Save the highest-IF power level in this trace:
                $lastdBm = $PW;

                // Accumulate min and max power seen for this LO:
                if ($IF >= self::LOW_IF_CUTOFF) {
                    if ($PW < $mindBm)
                        $mindBm = $PW;
                    if ($PW > $maxdBm)
                        $maxdBm = $PW;
                }

                // if new LO seen:
            } else {
                // output a row for previous LO:
                $appendResult($this->minMaxData, $this->pMin_dBm_overall, $this->pMax_dBm_overall, $lastLO, $mindBm, $maxdBm, $lastdBm);
                // and reset the min/max accumulators:
                $mindBm = self::HUGE_POWER;
                $maxdBm = self::TINY_POWER;
                // make it the new current LO:
                $lastLO = $LO;
            }
        }
        // output the final row:
        $appendResult($this->minMaxData, $this->pMin_dBm_overall, $this->pMax_dBm_overall, $lastLO, $mindBm, $maxdBm, $lastdBm);
        return $this->minMaxData;
    }

    public function getTotalPowerSpan_overall() {
        return $this->pMax_dBm_overall - $this->pMin_dBm_overall;
    }

    /**
     * If RF band edges were specified via setRFLimits() above,
     *  Return an array of coordinates for the marks:
     *
     *  @return array(
     *      [0] => array(
     *          'LO_GHz' => float,    // An LO frequency
     *          'Freq_GHz' => float   // The IF at the band edge
     *      )
     *      ...
     *  )
     *
     */
    public function getRFBandEdgeMarks($sb = self::USB) {
        $output = array();

        $LOs = $this->getLOs();
        if (!$LOs) {
            // no LOs found:
            return $output;
        }

        if ($this->rfMin == self::RFMIN_DEFAULT && $this->rfMax == self::RFMAX_DEFAULT) {
            // no RF band edges defined:
            return $output;
        }

        $outputRec = array(
            'LO_GHz' => self::BAD_LO,
            'Freq_GHz' => 0.0
        );

        foreach ($LOs as $LO) {
            $ifLim = false;

            // if USB and rfMax is defined:
            if ($sb == self::USB && $this->rfMax != self::RFMAX_DEFAULT) {
                // Compute IF limit of rfMax:
                $ifLim = $this->rfMax - $LO;

                // if LSB and rfMin is defined:
            } else if ($sb == self::LSB && $this->rfMin != self::RFMIN_DEFAULT) {
                // Compute IF limit of rfMin:
                $ifLim = $LO - $this->rfMin;
            }

            // append to output if a IF at the RF band edge was found:
            if ($ifLim) {
                $outputRec['LO_GHz'] = $LO;
                $outputRec['Freq_GHz'] = $ifLim;
                $output[] = $outputRec;
            }
        }
        return $output;
    }

    /**
     * Compute the in-band power variation vs. IF center frequency for all LOs.
     *
     * @param float $fMin Lower in-band IF in GHz
     * @param float $fMax Upper in-band IF in GHz
     * @param float $fWindow Moving window size for the power varation calculation, Hz.
     * @param $sb: self::USB or self::LSB
     *
     * @return array(
     *     [0] => array(
     *         'LO_GHz' => float,    // An LO frequency
     *         'Freq_GHz' => float,  // A center IF pertaining to the LO
     *         'pVar_dB' => float    // Max-min power seen in the window around Freq_GHz.
     *     )
     *     [1] => array...
     *
     */
    public function getPowerVarWindow($fMin = 4.0, $fMax = 8.0, $fWindow = 2.0, $spec = 0.0, $sb = self::USB) {
        if (empty($this->data))
            return false;

        $output = array();
        $this->badVarLOs = array();

        // get all the LO frequencies and their data ranges:
        $LORanges = $this->getLORanges();
        foreach ($LORanges as $range) {
            $LO = $range['LO_GHz'];
            $minIndex = $range['minIndex'];
            $maxIndex = $range['maxIndex'];

            // get the power variation plot points for each range, corresponding to one LO:
            $pvarPoints = $this->getPowerVarWindowForRange($minIndex, $maxIndex, $fMin, $fMax, $fWindow, $spec, $sb);

            // Append all the points to the output array:
            foreach ($pvarPoints as $row) {
                $output[] = array(
                    'LO_GHz' => $LO,
                    'Freq_GHz' => $row['Freq_GHz'],
                    'pVar_dB' => $row['pVar_dB']
                );
            }

            // If out of spec, append to list of bad LOs:
            if ($this->badVarThisRange)
                $this->badVarLOs[] = $LO;
        }
        return $output;
    }

    /**
     * Compute the in-band power variation vs. IF center frequency
     *  for a range of internal data structure indexes
     *
     * @param integer $minIndex Lower bound of range
     * @param integer $maxIndex Upper bound of range
     * @param float $fMin Lower in-band IF in GHz
     * @param float $fMax Upper in-band IF in GHz
     * @param float $fWindow Window size in GHz
     * @param $sb: self::USB or self::LSB
     *
     * @return array(
     *     [0] => array(
     *         'Freq_GHz' => float,
     *         'pVar_dB' => float
     *     )
     *     [1] => array...
     * }
     */
    private function getPowerVarWindowForRange($minIndex, $maxIndex, $fMin = 4.0, $fMax = 8.0, $fWindow = 2.0, $spec = 0.0, $sb = self::USB) {

        if (defined('DEBUG_IFSPECTRUM_CALC'))
            $this->logger1->WriteLogFile("getPowerVarWindowForRange($minIndex, $maxIndex, $fMin, $fMax, $fWindow, $spec, $sb)");

        $output = array();

        // sanity check inputs:
        if (empty($this->data))
            return false;

        if ($fMin > $fMax)
            return false;

        if ($fWindow <= self::MIN_IF_BIN)
            return false;

        $this->badVarThisRange = false;

        // compute lower and upper center frequencies:
        $fLower = $fMin + ($fWindow / 2);
        $fUpper = $fMax - ($fWindow / 2);

        if (defined('DEBUG_IFSPECTRUM_CALC'))
            $this->logger1->WriteLogFile("fLower=$fLower fUpper=$fUpper");

        // find the index of the lower center frequency:
        $iLower = $this->findWindowEdge($minIndex, $maxIndex, $fLower, false);
        if ($iLower === false)
            return false;

        // find the index of the upper center frequency:
        $iUpper = $this->findWindowEdge($minIndex, $maxIndex, $fUpper, true);
        if ($iUpper === false)
            return false;

        // adjust in case $fLower == $fUpper:
        if ($fLower == $fUpper && $iUpper < $iLower)
            $iLower = $iLower;

        if (defined('DEBUG_IFSPECTRUM_CALC'))
            $this->logger1->WriteLogFile("iLower=$iLower iUpper=$iUpper");

        // find the index of the lower window edge:
        $iLowerWindow = $this->findWindowEdge($minIndex, $iLower, $fMin, false);

        // assuming the frequency data is evenly spaced, find the window span in index counts:
        $iSpan = (int)$iLower - (int)$iLowerWindow;

        if (defined('DEBUG_IFSPECTRUM_CALC'))
            $this->logger1->WriteLogFile("iLowerWindow=$iLowerWindow iSpan=$iSpan");

        // Use sb to set the sign when we add the IF to the LO below:
        $sb = ($sb == self::LSB) ? -1.0 : 1.0;

        $first = true;

        // loop the window center from the lower to upper index:
        for ($iCenter = (int)$iLower; $iCenter <= $iUpper; $iCenter++) {
            $fCenter = $this->data[$iCenter]['Freq_GHz'];
            $LO = $this->data[$iCenter]['LO_GHz'];

            // compute window edge for comparing to rfMin or rfMax:
            $windowEdge = $LO + ($sb * ($fCenter + ($fWindow / 2)));

            $mindBm = self::HUGE_POWER;
            $maxdBm = self::TINY_POWER;
            $valid = false;
            // loop across the window around the moving center:
            $iWinMin = $iCenter - $iSpan;
            $iWinMax = $iCenter + $iSpan;

            if ($first && defined('DEBUG_IFSPECTRUM_CALC')) {
                $this->logger1->WriteLogFile("LO=$LO fCenter=$fCenter iWinMin=$iWinMin iWinMax=$iWinMax windowEdge=$windowEdge");
                $first = false;
            }

            for ($index = $iWinMin; $index <= $iWinMax; $index++) {
                $row = $this->data[$index];
                $IF = $row['Freq_GHz'];

                // Limit to specified RF range, if any:
                if ($windowEdge >= $this->rfMin && $windowEdge <= $this->rfMax) {
                    //  find min and max power in the window:
                    $valid = true;
                    $PW = $row['Power_dBm'];
                    if ($PW < $mindBm)
                        $mindBm = $PW;
                    if ($PW > $maxdBm)
                        $maxdBm = $PW;
                }
            }
            // calculate pVar:
            if ($valid) {
                $pVar = $maxdBm - $mindBm;
                // append output record:
                $output[] = array(
                    'Freq_GHz' => $fCenter,
                    'pVar_dB' => $pVar
                );
                // accumulate $maxVarWindow:
                if ($pVar > $this->maxVarWindow)
                    $this->maxVarWindow = $pVar;
                // accumulate $badVarThisRange:
                if ($spec > 0 && $pVar > $spec)
                    $this->badVarThisRange = true;
            }
        }
        return $output;
    }

    /**
     * Find the lower bound of $findFreq in the specified range:
     *  first index where $findFreq >= Freq_GHz
     *  If $reverse is true, find the upper bound:
     *  last index where $findFreq <= Freq_GHz
     *
     * @param integer $minIndex of range to search
     * @param integer $maxIndex of range to search
     * @param float $findFreq Freq_GHz to find in range
     * @param boolean $reverse true if searching down from $maxIndex
     * @return index value or false if not found.
     */
    private function findWindowEdge($minIndex, $maxIndex, $findFreq, $reverse) {
        $index = ($reverse) ? $maxIndex : $minIndex;
        $done = false;
        while (!$done) {
            $freq = $this->data[$index]['Freq_GHz'];
            if (!$reverse) {
                if ($freq >= $findFreq)
                    $done = true;
                else {
                    $index++;
                    if ($index > $maxIndex)
                        return false;
                }
            } else {
                if ($freq <= $findFreq)
                    $done = true;
                else {
                    $index--;
                    if ($index < $minIndex)
                        return false;
                }
            }
        }
        return $index;
    }

    /**
     * Compute the full-band power variation and min/max powers seen for all LOs.
     *
     * @param float $fMin Lower in-band IF in GHz
     * @param float $fMax Upper in-band IF in GHz
     * @return array(
     *     [0] => array(
     *         'LO_GHz' => float,
     *         'pVar_dB' => float,
     *         'pMin_dBm' => float,
     *         'pMax_dBm' => float
     *     )
     *     [1] => array...
     * }
     */
    public function getPowerVarFullBand($fMin = 4.0, $fMax = 8.0, $sb = self::USB) {
        // helper function to append a record to the output:
        $appendResult = function (&$output, $LO, $mindBm, $maxdBm) {
            $output[] = array(
                'LO_GHz' => $LO,
                'pVar_dB' => ($maxdBm - $mindBm),
                'pMin_dBm' => $mindBm,
                'pMax_dBm' => $maxdBm
            );
        };

        if (empty($this->data))
            return false;

        $output = array();
        // record structure to accumulate min and max for each LO:
        $lastLO = self::BAD_LO;
        $mindBm = self::HUGE_POWER;
        $maxdBm = self::TINY_POWER;

        $sb = ($sb == self::LSB) ? -1.0 : 1.0;

        // loop on all rows:
        foreach ($this->data as $row) {
            $LO = $row['LO_GHz'];
            $IF = $row['Freq_GHz'];
            $PW = $row['Power_dBm'];

            // for each new LO seen:
            if ($LO != $lastLO) {
                // if the previous LO seen is not our start token:
                if ($lastLO != self::BAD_LO) {
                    // output the previous LO record:
                    $appendResult($output, $lastLO, $mindBm, $maxdBm);
                    if (defined('DEBUG_IFSPECTRUM_CALC'))
                        $this->logger1->WriteLogFile("getPowerVarFullBand: $lastLO, $mindBm, $maxdBm");
                }
                // reset the output record to accumulate for the next LO:
                $lastLO = $LO;
                // reset min/max accumulators:
                $mindBm = self::HUGE_POWER;
                $maxdBm = self::TINY_POWER;
            }

            $RF = $LO + ($IF * $sb);
            // Limit to specified RF range, if any:
            if ($RF >= $this->rfMin && $RF <= $this->rfMax) {
                // accumulate min and max powers seen for the current LO:
                if ($fMin <= $IF && $IF <= $fMax) {
                    if ($PW < $mindBm)
                        $mindBm = $PW;
                    if ($PW > $maxdBm)
                        $maxdBm = $PW;
                }
            }
        }
        // output data for the last LO:
        $appendResult($output, $lastLO, $mindBm, $maxdBm);
        if (defined('DEBUG_IFSPECTRUM_CALC'))
            $this->logger1->WriteLogFile("getPowerVarFullBand: $lastLO, $mindBm, $maxdBm");
        return $output;
    }

    /**
     * Compute the total and in-band power for all LOs.
     * @param float $fMin Lower in-band IF in GHz
     * @param float $fMax Upper in-band IF in GHz
     * @return array(
     *     [0] => array(
     *         'LO_GHz' => float,
     *         'pTotal_dBm' => float,
     *         'pInBand_dBm' => float
     *     )
     *     [1] => array...
     */
    public function getTotalAndInBandPower($fMin = 4.0, $fMax = 8.0) {
        // helper function to append a record to the output:
        $appendResult = function (&$output, $LO, $total, $inBand) {
            // accumulated powers converted back to dBm:
            $output[] = array(
                'LO_GHz' => $LO,
                'pTotal_dBm' => 10 * log($total, 10),
                'pInBand_dBm' => 10 * log($inBand, 10)
            );
        };

        if (empty($this->data))
            return false;

        // save the existing data in a temp variable:
        $tempData = $this->data;

        // subtract the noise floor from the data set:
        $this->subtractNoiseFloor();

        $output = array();

        $total = 0;
        $inBand = 0;
        $lastLO = self::BAD_LO;

        // loop on all rows:
        foreach ($this->data as $row) {
            $LO = $row['LO_GHz'];
            $IF = $row['Freq_GHz'];
            $PW = $row['Power_dBm'];
            // for each new LO seen:
            if ($LO != $lastLO) {
                // if the previous LO seen is not our start token:
                if ($lastLO != self::BAD_LO) {
                    // output a record with the accumulated powers:
                    $appendResult($output, $LO, $total, $inBand);
                }
                // reset our accumulators:
                $lastLO = $LO;
                $total = 0;
                $inBand = 0;
            }
            // convert dBm to mW:
            $P = pow(10, ($PW + $this->cablePad) / 10);
            // accumulate total power, typically not including below 10 MHz:
            if ($IF >= self::LOW_IF_CUTOFF)
                $total += $P;
            // accumulate in-band power:
            if ($fMin <= $IF && $IF <= $fMax)
                $inBand += $P;
        }
        // output the record for the last LO:
        $appendResult($output, $LO, $total, $inBand);

        // restore original data prior to NF correction:
        $this->data = $tempData;

        return $output;
    }

    /**
     * Modify the internal data array by subtracting the noise floor.
     */
    private function subtractNoiseFloor() {
        if (empty($this->data))
            return;
        if (empty($this->noiseFloorData))
            return;

        // We assume that the $noiseFloorData array has the same number and of points
        //  at the same Freq_GHz indexes as each of the LO sections of $data.

        // Loop for all rows:
        $nfIndex = 0;
        $size = count($this->data);
        $lastLO = self::BAD_LO;
        for ($index = 0; $index < $size; $index++) {
            $row = $this->data[$index];
            $LO = $row['LO_GHz'];
            $PW = $row['Power_dBm'];
            // if new LO seen:
            if ($LO != $lastLO) {
                // make it the new current LO:
                $lastLO = $LO;
                // reset the index into the noise floor data:
                $nfIndex = 0;
            }

            // Convert the spectrum data and noise floor data into milliwatts:
            $P = pow(10, $PW / 10);
            $floor = pow(10, $this->noiseFloorData[$nfIndex]['Power_dBm'] / 10);

            // Subtract the noise floor:
            if ($P <= $floor)
                // if we can't subtract the floor from the power level, just return a very small quantity of power:
                $P = self::SMALL_MW;
            else
                $P -= $floor;

            // Stuff the result back into the spectrum data:
            $this->data[$index]['Power_dBm'] = 10 * log($P, 10);
            // Increment the noise floor data index for the next loop iter:
            $nfIndex++;
        }
    }
}
