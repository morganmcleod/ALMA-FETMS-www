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

class IFSpectrum_calc {
    private $data;
    private $noiseFloorData;
    private $cablePad;

    const BAD_LO = -999;        // GHz
    const HUGE_POWER = 999;     // dBm
    const TINY_POWER = -999;    // dBm
    const MIN_IF_BIN = 3.0e6;   // Hz   We assume the analyzer bins are 3 MHz.

    /**
     * Constructor
     *
     * @param $data structure is:
     * array(
     *     [0] => Array(
     *          'LO_GHz' => float,    // LO frequencies
     *          'Freq_Hz' => float,   // Spectrum analyzer IF centers
     *          'Power_dBm' => float  // Spectrum analyzer power measurements
     *     )
     *     [1] => Array...
     * )
     */
    public function IFSpectrum_calc($data) {
        setData($data);
    }

    /**
     * Asssign new data.
     *
     * @param $data structure given above.
     */
    public function setData($data) {
        $this->data = $data;
        $sortData();
    }

    /**
     * Sort the data ascending by LO_GHz, Freq_Hz.
     */
    public function sortData() {
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
    public function getLORanges() {
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
     *
     * @return array(
     *     [0] => array(
     *         'LO_GHz' => float,    // An LO frequency
     *         'Freq_Hz' => float,   // A center IF pertaining to the LO
     *         'pVar_dB' => float    // Max-min power seen in the window around Freq_Hz.
     *     )
     *     [1] => array...
     *
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
     * @return array(
     *     [0] => array(
     *         'Freq_Hz' => float,
     *         'pVar_dB' => float
     *     )
     *     [1] => array...
     * }
     *
     * @param integer $minIndex
     * @param integer $maxIndex
     * @param float $fMin
     * @param float $fMax
     * @param float $fWindow
     */
    private function getPowerVarWindowForRange($minIndex, $maxIndex, $fMin = 4.0e9, $fMax = 8.0e9, $fWindow = 2.0e9) {
        $output = array();

        // sanity check inputs:
        if (empty($this->data))
            return false;

        if ($fMin > $fMax)
            return false;

        if ($fWindow <= this::MIN_IF_BIN)
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
            $mindB = this::HUGE_POWER;
            $maxdB = this::TINY_POWER;
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
     * Compute the full-band power variation and min/max powers seen
     *  for every LO frequency.
     *
     * output structure is:
     * array(
     *     [0] => array(
     *         'LO_GHz' => float,
     *         'pVar_dB' => float,
     *         'pMin_dBm' => float,
     *         'pMax_dBm' => float
     *     )
     *     [1] => array...
     * }
     *
     * @param float $fMin
     * @param float $fMax
     */
    public function getPowerVarFullBand($fMin = 4.0e9, $fMax = 8.0e9) {
        if (empty($this->data))
            return false;

        $output = array();
        $newRec = array(
            'LO_GHz' => self::BAD_LO,
            'pVar_dB' => 0,
            'pMin_dBm' => self::HUGE_POWER,
            'pMax_dBm' => self::TINY_POWER
        );
        $outputRec = $newRec;

        foreach($this->data as $row) {
            $LO = $row['LO_GHz'];
            $IF = $row['Freq_Hz'];
            $PW = $row['Power_dBm'];

            if ($LO != $outputRec[0]) {
                if ($outputRec[0] != self::BAD_LO) {
                    $outputRec[1] = $outputRec[3] - $outputRec[2];
                    $output[] = $outputRec;
                }
                $outputRec = $newRec;
                $outputRec[0] = $LO;
            }

            if ($fMin <= $IF && $IF <= $fMax) {
                if ($PW < $outputRec[2])
                    $outputRec[2] = $PW;
                if ($PW > $outputRec[3])
                    $outputRec[3] = $PW;
            }
        }
        // output data for the last LO:
        $outputRec[1] = $outputRec[3] - $outputRec[2];
        $output[] = $outputRec;
        return $output;
    }

}

?>
