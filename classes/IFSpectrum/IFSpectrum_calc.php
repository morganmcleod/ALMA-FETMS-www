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

interface IFSpectrum_calc_itf {
    /**
     * Asssign new data.
     *
     * @param $data structure is:
     * array(
     *     [0] => Array(
     *          'LO_GHz' => float,    // LO frequency
     *          'Freq_Hz' => float,   // Spectrum analyzer IF center
     *          'Power_dBm' => float  // Spectrum analyzer power measurement
     *     )
     *     [1] => Array...
     * )
     */
    public function setData($data);

    /**
     * Assign noise floor trace to use for correcting data traces.
     *
     * @param $data array must have the same number of points as the per-LO traces.  Structure is:
     * array(
     *     [0] => Array(
     *          'Freq_Hz' => float,   // Spectrum analyzer IF center
     *          'Power_dBm' => float  // Spectrum analyzer power measurement
     *     )
     *     [1] => Array...
     * )
     */
    public function setNoiseFloorData($nfData);

    /**
     * Get an array of distinct LOs in the data set.
     */
    public function getLOs();

    /**
     * Compute the in-band power variation vs. IF center frequency for all LOs.
     *
     * @param float $fMin Lower in-band IF in Hz
     * @param float $fMax Upper in-band IF in Hz
     * @param float $fWindow Moving window size for the power varation calculation, Hz.
     * @return array(
     *     [0] => array(
     *         'LO_GHz' => float,    // An LO frequency
     *         'Freq_Hz' => float,   // A center IF pertaining to the LO
     *         'pVar_dB' => float    // Max-min power seen in the window around Freq_Hz.
     *     )
     *     [1] => array...
     *
     */
    public function getPowerVarWindow($fMin = 4.0e9, $fMax = 8.0e9, $fWindow = 2.0e9);

    /**
     * Compute the full-band power variation and min/max powers seen for all LOs.
     *
     * @param float $fMin Lower in-band IF in Hz
     * @param float $fMax Upper in-band IF in Hz
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
    public function getPowerVarFullBand($fMin = 4.0e9, $fMax = 8.0e9);

    /**
     * Compute the total and in-band power for all LOs.
     * @param float $fMin Lower in-band IF in Hz
     * @param float $fMax Upper in-band IF in Hz
     * @return array(
     *     [0] => array(
     *         'LO_GHz' => float,
     *         'pTotal_dBm' => float,
     *         'pInBand_dBm' => float
     *     )
     *     [1] => array...
     */
    public function getTotalAndInBandPower($fMin = 4.0e9, $fMax = 8.0e9);

    /**
     * Apply offsets in dB to the power levels in dBm of subsequent LO traces.
     *  This is a utility for plotting, to spread out the traces for display on a single Y scale.
     *
     * @param float $offset Amount to offset each LO trace from the previous.
     * @return none.  Modifies the internal data array.
     */
    public function applyPowerLevelOffsets($offset);

}


class IFSpectrum_calc implements IFSpectrum_calc_itf {
    private $data;                // same structure as for setData()
    private $noiseFloorData;
    private $cablePad;            // dB of pad in cable to compensate for.

    const BAD_LO = -999;          // GHz  Invalid value for LO
    const HUGE_POWER = 999;       // dBm  Invalid big value for power
    const TINY_POWER = -999;      // dBm  Invalid small value for power
    const TINY_DBM = 1.0e-9;      // dBm  Minimum result to return when subtracting noise floor.
    const MIN_IF_BIN = 3.0e6;     // Hz   We assume the analyzer bins are 3 MHz.
    const LOW_IF_CUTOFF = 10.0e6; // exlude power from below 10 MHz from total power calc.
    const DFLT_CABLEPAD = 6.0;    // dB   Assumed cable pad to compensate for.

    /**
     * Constructor
     *
     * @param $data structure shown for setData in interface above.
     */
    public function IFSpectrum_calc($data = false) {
        $this->cablePad = self::DEFAULT_CABLEPAD;
        $rhis->$noiseFloorData = array();
        if ($data)
            $this->setData($data);
    }

    /**
     * Asssign new data.
     *
     * @param $data structure shown in interface above.
     */
    public function setData($data) {
        $this->data = $data;
        $this->sortData();
    }

    /**
     * Assign noise floor trace to use for correcting data traces.
     *
     * @param $data structure shown in interface above.
     */
    public function setNoiseFloorData($nfData) {
        $this->noiseFloorData = $nfData;
    }

    /**
     * Sort the data ascending by LO_GHz, Freq_Hz.
     */
    private function sortData() {
        function cmp(array $a, array $b) {
            if ($a[0] != $b[0])
                return $a[0] - $b[0];
            else
                return $a[1] - $b[1];
        }
        usort($this->data, "cmp");
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
        foreach($this->data as $row) {
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
            } else {
                // Same LO as previously so increas the max index:
                $outputRec['maxIndex'] = $index;
            }
            // increment for the next iteration:
            $index++;
        }
        // append the final record:
        $output[] = $outputRec;
        return $output;
    }

    /**
     * Compute the in-band power variation vs. IF center frequency
     *   for all LOs
     *
     * @param float $fMin Lower in-band IF in Hz
     * @param float $fMax Upper in-band IF in Hz
     * @param float $fWindow Moving window size for the power varation calculation, Hz.
     * @return data structure shown in interface above.
     */
    public function getPowerVarWindow($fMin = 4.0e9, $fMax = 8.0e9, $fWindow = 2.0e9) {
        if (empty($this->data))
            return false;

        $output = array();

        // get all the LO frequencies and their data ranges:
        $LORanges = $this->getLORanges();
        foreach ($LORanges as $range) {
            $LO = $LORanges['LO_GHz'];
            $minIndex = $LORanges['minIndex'];
            $maxIndex = $LORanges['maxIndex'];

            // get the power variation plot points for each range, corresponding to one LO:
            $pvarPoints = getPowerVarWindowForRange($minIndex, $maxIndex, $fMin, $fMax, $fWindow);

            // Append all the points to the output array:
            foreach ($pvarPoints as $row) {
                $output[] = array(
                        'LO_GHz' => $LO,
                        'Freq_Hz' => $row['Freq_Hz'],
                        'pVar_dB' => $row['pVar_dB']
                );
            }
        }
        return $output;
    }

    /**
     * Compute the in-band power variation vs. IF center frequency
     *  for a range of internal data structure indexes
     *
     * @param integer $minIndex Lower bound of range
     * @param integer $maxIndex Upper bound of range
     * @param float $fMin Lower in-band IF in Hz
     * @param float $fMax Upper in-band IF in Hz
     * @param float $fWindow Window size in Hz
     *
     * @return array(
     *     [0] => array(
     *         'Freq_Hz' => float,
     *         'pVar_dB' => float
     *     )
     *     [1] => array...
     * }
     */
    private function getPowerVarWindowForRange($minIndex, $maxIndex, $fMin = 4.0e9, $fMax = 8.0e9, $fWindow = 2.0e9) {
        $output = array();

        // sanity check inputs:
        if (empty($this->data))
            return false;

        if ($fMin > $fMax)
            return false;

        if ($fWindow <= self::MIN_IF_BIN)
            return false;

        // compute lower and upper center frequencies:
        $fLower = $fMin + ($fWindow / 2);
        $fUpper = $fMax - ($fWindow / 2);

        // find the first index greater than or equal to the lower center frequency:
        $iLower = $minIndex;
        while ($this->data[$iLower]['Freq_Hz'] < $fLower) {
            $iLower++;
            // fail if we hit the other end of the range:
            if ($iLower > $maxIndex)
                return false;
        }

        // find the index of the upper center frequency:
        $iUpper = $maxIndex;
        while ($this->data[$iUpper]['Freq_Hz'] > $fUpper) {
            $iUpper--;
            // fail if we hit the other end of the range:
            if ($iUpper < $minIndex)
                return false;
        }

        // compute the window size in indexes above and below the window centers:
        $iSpan = $iLower - $minIndex;

        // loop the window center from the lower to upper index:
        for ($iCenter = $iLower; $iCenter <= $iUpper; $iCenter++) {
            $fCenter = $this->data[$iCenter]['Freq_Hz'];
            $mindB = self::HUGE_POWER;
            $maxdB = self::TINY_POWER;
            // loop across the window around the moving center:
            $iWinMin = $iCenter - $iSpan;
            $iWinMax = $iCenter + $iSpan;
            for ($index = $iWinMin; $index <= $iWinMax; $index++) {
                $row = $this->data[$index];
                $PW = $row['Power_dBm'];
                // find min and max power in the window:
                if ($PW < $mindB)
                    $mindB = $PW;
                if ($PW > $maxdB)
                    $maxdB = $PW;
            }
            // append output record:
            $output[] = array(
                    'Freq_Hz' => $fCenter,
                    'pVar_dB' => ($maxdB - $mindB)
            );
        }
        return $output;
    }

    /**
     * Compute the full-band power variation and min/max powers seen for all LOs.
     *
     * @param float $fMin Lower in-band IF in Hz
     * @param float $fMax Upper in-band IF in Hz
     * @return data structure shown in interface above.
     */
    public function getPowerVarFullBand($fMin = 4.0e9, $fMax = 8.0e9) {
        // helper function to append a record to the output:
        function appendResult(&$output, $outputRec) {
            // compute the power difference and append:
            $outputRec[1] = $outputRec[3] - $outputRec[2];
            $output[] = $outputRec;
        }

        if (empty($this->data))
            return false;

        $output = array();
        // record structure to accumulate min and max for each LO:
        $newRec = array(
            'LO_GHz' => self::BAD_LO,
            'pVar_dB' => 0,
            'pMin_dBm' => self::HUGE_POWER,
            'pMax_dBm' => self::TINY_POWER
        );
        $outputRec = $newRec;

        // loop on all rows:
        foreach($this->data as $row) {
            $LO = $row['LO_GHz'];
            $IF = $row['Freq_Hz'];
            $PW = $row['Power_dBm'];

            // for each new LO seen:
            if ($LO != $outputRec[0]) {
                // if the previous LO seen is not our start token:
                if ($outputRec[0] != self::BAD_LO) {
                    // output the previous LO record:
                    appendResult(&$output, $outputRec);
                }
                // reset the output record to accumulate for the next LO:
                $outputRec = $newRec;
                $outputRec[0] = $LO;
            }

            // accumulate min and max powers seen for the current LO:
            if ($fMin <= $IF && $IF <= $fMax) {
                if ($PW < $outputRec[2])
                    $outputRec[2] = $PW;
                if ($PW > $outputRec[3])
                    $outputRec[3] = $PW;
            }
        }
        // output data for the last LO:
        appendResult(&$output, $outputRec);
        return $output;
    }

    /**
     * Compute total and in-band power for all LOs.
     *
     * @param float $fMin Lower in-band IF in Hz
     * @param float $fMax Upper in-band IF in Hz
     * @return data structure shown in interface above.
     */
    public function getTotalAndInBandPower($fMin = 4.0e9, $fMax = 8.0e9) {
        // helper function to append a record to the output:
        function appendResult(&$output, $LO, $total, $inband) {
            // accumulated powers converted back to dBm:
            $output[] = array(
                    'LO_GHz' => $LO,
                    'pTotal_dBm' => 10 * log($total, 10),
                    'pInBand_dBm' => 10 * log($inBand, 10)
            );
        }

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
        foreach($this->data as $row) {
            $LO = $row['LO_GHz'];
            $IF = $row['Freq_Hz'];
            $PW = $row['Power_dBm'];
            // for each new LO seen:
            if ($LO != $lastLO) {
                // if the previous LO seen is not our start token:
                if ($lastLO != self::BAD_LO) {
                    // output a record with the accumulated powers:
                    appendResult($output, $LO, $total, $inBand);
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
        appendResult($output, $LO, $total, $inBand);
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
        //  at the same Freq_Hz indexes as each of the LO sections of $data.

        // Loop for all rows:
        $nfIndex = 0;
        $size = count($this->data);
        $lastLO = self::BAD_LO;
        for ($index = 0; $index < size; $index++) {
            $row = $this->data[$index];
            $LO = $row['LO_GHz'];
            $PW = $row['Power_dBm'];
            // if new LO seen:
            if ($LO != $lastLO) {
                // make make it the new current LO:
                $lastLO = $LO;
                // reset the index into the noise floor data:
                $nfIndex = 0;
            }

            // Convert the spectrum data and noise floor data into milliwatts:
            $P = pow(10, $PW / 10);
            $floor = pow(10, $this->noiseFloorData[$nfIndex]['Power_dBm'] / 10);

            // Subtract the noise floor:
            if ($P <= $floor)
                // if we can't subtract the floor from the power level, just use a tiny quantity of power.
                $P = self::TINY_DBM;
            else
                $P -= $floor;

            // Stuff the result back into the spectrum data:
            $this->data[$index]['Power_dBm'] = 10 * log($P, 10);
            // Increment the noise floor data index for the next loop iter:
            $nfIndex++;
        }
    }

    /**
     * Apply offsets in dB to the power levels in dBm of subsequent LO traces.
     *  This is a utility for plotting, to spread out the traces for display on a single Y scale.
     *
     * @param float $offset Amount to offset each LO trace from the previous.
     * @return none.  Modifies the internal data array.
     */
    public function applyPowerLevelOffsets($offset) {
        if (empty($this->data))
            return;

        // to accumulate offset of all prev. LOs:
        $totalOffset = 0;

        // Loop for all rows:
        $size = count($this->data);
        $lastLO = self::BAD_LO;
        for ($index = 0; $index < size; $index++) {
            $row = $this->data[$index];
            $LO = $row['LO_GHz'];
            $PW = $row['Power_dBm'];
            // if new LO seen:
            if ($LO != $lastLO) {
                // make make it the new current LO:
                $lastLO = $LO;
                // increase the total offset amount:
                $totalOffset += $offset;
            }
            // Apply the offset to the current row:
            $this->data[$index][Power_dBm] = $PW + $totalOffset;
        }
    }
}

?>
