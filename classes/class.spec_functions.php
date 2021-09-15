<?php
/**
 * Author: Aaron Beaudoin
 * Modified version of spec_functions.php, previously written and owned by NRAO.
 */
    require_once(dirname(__FILE__) . '/../SiteConfig.php');

    /**
    * Initializes the interface for specifications to be used by the class specs.
    */
    interface SpecRetrieval {
        public function getSpecs($test_type, $band, $spec_name=None);
        public function inspec($num, $inspec);
        public function chkNumAgnstSpec($num, $operator, $spec);
        public function numInRange($hi_range, $lo_range, $num);
        public function numWithinPercent($num, $num2, $spec);
        public function percentDevFromAvg($y, $x, $spec, $file);
        public function percentDifAdjacentPts($y, $x, $spec, $file);
        public function stdevChks($y, $x, $win_size, $stdev_spec, $file);
    }

    /**
    * Initialized the class specs.
    */
    class Specifications implements SpecRetrieval {

        /**
        * Constructor for class specs.
        * No parameters.
        */
        public function Specifications() {}

        /**
        * Returns 2D array of specifications with their test type, spec type, and band.
        *
        * @param $test_type (array) - array of strings indicating the test types
        * @param $band (array) - array of integers indicating the band number (ex. 5 for "Band5")
        * @param $spec_name (array) - array of strings indicating the desired specifications
        *
        * @return (array) returns a 2D array, where each subarray consists of
        * four values, the test type, band, spec type, and the specification, respectively.
        */
        public function getSpecs($test_type, $Band, $spec_name = array()) {
            if(!is_array($test_type)) {
                $test_type = array($test_type);
            }
            if(!is_array($Band)) {
                $Band = array($Band);
            }
            $specs = array();
            $tLen = count($test_type);
            $bLen = count($Band);
            $sLen = count($spec_name);
            //Sorts through each array to find the specification for each band for each test type.

            for($t=0; $t<$tLen; $t++) {
                $filename = dirname(__FILE__) . "/../specs/" . $test_type[$t] . ".ini";
                $test=parse_ini_file($filename,true);
                for($b=0; $b<$bLen; $b++) {
                    $band_s = (string)$Band[$b];
                    $band_s = "Band" . $band_s;
                    $spec_names = array();
                    if(empty($spec_name)) {
                        $temp = array_keys($test[$band_s]);
                        foreach ($temp as $a) {
                            $spec_names[] = $a;
                        }
                    } else {
                        $spec_names = $spec_name;
                    }
                    $sLen = count($spec_names);
                    for($s=0; $s<$sLen; $s++) {
                        $ans = $test[$band_s][$spec_names[$s]];
                        $specs[$spec_names[$s]] = $ans;
                    }
                }
            }
            return $specs;
        }

        /**
         * Returns an HTML string with $num encoded green or red based
         * on an integer value $inspec.
         *
         * @param $num (float) - number to display
         * @param $inspec (boolean) - If true, number is green, if false, number is red.
         *
         * @return (string) returns an HTML string with $num and color encoding indicating
         * meeting spec.
        */
        public function inspec($num, $inspec) {
            if ($inspec) {
                $result = "<font color='#008000'>$num</font>";
            } else {
                $result = "<font color='#ff0000'>$num</font>";
            }
            return $result;
        }

        /**
         * Returns an HTML string with $num encoded green or red based
         * on if $num mets the $spec according to the operator ">", "<" or "=".
         *
         * @param $num (float) - number to compare against spec
         * @param $operator (string) - one of three strings, ">", "<" or "=".
         * @param $spec (float) - specification
         *
         *
         * @return (string) returns and HTML string with $num and color encoding indicating
         * meeting spec.
         */
        public function chkNumAgnstSpec($num, $operator, $spec, $spec2=0) {
            $inspec = false;
            switch ($operator) {
                case ">":
                    if ($num > $spec)
                        $inspec = true;
                    break;

                case "<":
                    if ($num < $spec)
                        $inspec = true;
                    break;

                case "=":
                    if ($num = $spec)
                        $inspec = true;
                    break;

                case "range":
                    if ($num >= $spec && $num <= $spec2)
                        $inspec = true;
                    break;

                default;
                    $inspec = false;
                    break;
            }
            $resp = $this->inspec($num, $inspec);
            return $resp;
        }

        /**
         * Returns an HTML string with $num encoded green or red based
         * on if $num is in the specified range.
         *
         * @param $hi_range (float) - upper limit of range
         * @param $num (float) - parameter to test if in range
         * @param $low_range (float) - lower limit of range
         *
         * @return (string) returns and HTML string with $num and color encoding indicating
         * $num is in range.
         */
        public function numInRange($hi_range, $num, $lo_range) {
            if ( ($num < $hi_range) && ($num > $lo_range)) {
                $inspec = true;
            } else {
                $inspec = false;
            }
            $resp = $this->inspec($num, $inspec);
            return $resp;
        }

        /**
         * Returns an HTML string with $num encoded green or red based
         * on if $num is within $spec percent of $num2.
         *
         * @param $num (float) - parameter return colored
         * @param $num2 (float) - value to compare $num to
         * @param $spec (float) - percent specification
         *
         * @return (string) returns and HTML string with $num and color encoding indicating
         * $num is within $spec percent of $num2.
         */
        public function numWithinPercent($num, $num2, $spec) {
            if ($num2 > -1 && $num2 < 1) {
                $hi_range = $num2 + 1 * $spec/100;
                $lo_range = $num2 - 1 * $spec/100;
            } else {
                $hi_range = $num2 + abs($num2*($spec/100));
                $lo_range = $num2 - abs($num2*($spec/100));
            }
            $resp = $this->numInRange($hi_range, $num, $lo_range);
            return $resp;
        }
        //TODO check client code for need for files
        /**
         * Checks to see if each value in the array $y is outside a percent deviation
         * specification and write each outlyer point to a file to plot.
         *
         * @param $y (array) - array of y values
         * @param $x (array) - array of x values
         * @param $spec (float) - percent deviation spec
         * @param $file (float) - file to write results to
         *
         */
        public function percentDevFromAvg($y, $x, $spec, $file) {
            $index = 0;
            $pt_cnt =0;
            $avg_input = array_sum($y)/count($y);
            foreach ($y as $input) {
                if (($input > $avg_input * (1+$spec/100)) || ($input < $avg_input * (1-$spec/100))) {
                    $writestring = "$x[$index]\t$input\t$avg_input\r\n";
                    fwrite($file,$writestring);
                    $pt_cnt++;
                }
                $index++;
            }
            //TODO move dummy point into calling code
            if ($pt_cnt == 0) {
                // write dummy point if no data is flagged so plots will work
                $writestring = "$x[0]\t10000\t10000\r\n";
                fwrite($file,$writestring);
            }
        }

        /**
         * Checks to see if each value in the array $y varies from an adjacent
         * point by a specified percentage and writes each out-of-spec
         * point to a file to plot.
         *
         * @param $y (array) - array of y values
         * @param $x (array) - array of x values
         * @param $spec (float) - percent adjacent point spec  spec
         * @param $file (float) - file to write results to
         *
         */
        public function percentDifAdjacentPts($y, $x, $spec, $file) {
            $index = 0;
            $pt_cnt = 0;
            foreach ($y as $input) {
                $index2 = $index-1;
                $percent_diff = abs(($y[$index2]-$y[$index])/(($y[$index2]+$y[$index])/2))*100;
                // if % difference between current point and previous point is great than spec...
                if ($index != 0 && $percent_diff > $spec ) {
                    $writestring = "$x[$index]\t$input\t$percent_diff\r\n";
                    fwrite($file,$writestring);
                    $pt_cnt++;
                }
                $index++;
            }
            if ($pt_cnt == 0) {
                // write dummy point if no data is flagged so plots will work
                $writestring = "$x[0]\t10000\t10000\r\n";
                fwrite($file,$writestring);
            }
        }

        /**
         * Checks for points that are out side of a standard deviation threashold
         * in a window of a given width
         *
         * @param $y (array) - array of y values
         * @param $x (array) - array of x values
         * @param $win_size (float) - # of points of window
         * @param $stdev_spec (float) - stdev multiplication factor
         * @param $file (float) - file to write results to
         *
         */
        public function stdevChks($y, $x, $win_size, $stdev_spec, $file) {
            $half_win_size =  round($win_size/2, 0, PHP_ROUND_HALF_DOWN);
            $index = 0;
            $pt_cnt = 0;
            $y_cnt = count($y);
            foreach ($y as $input ){
                // this is where the moving average start value is determined.
                $low_pt = $index - $half_win_size;
                if ($low_pt < 0) {
                    $low_pt = 0;
                } else if ($low_pt + $win_size > ($y_cnt-1) ) {
                    $low_pt = $y_cnt - $win_size;
                }
                // get moving average subset of the entire data set
                $array_subset = array_slice($y, $low_pt, $win_size);
                // calculate average of subset
                $avg = array_sum($array_subset)/$win_size;
                // calculate standard deviation of subset
                $sum_squares = 0;
                foreach ($array_subset as $y1) {
                    $sum_squares = pow($y1-$avg,2)+$sum_squares;
                }
                $stdev = sqrt($sum_squares/$win_size);
                // if % difference between current point and previous point is great than spec...
                if ($stdev > $stdev_spec ) {
                    $writestring = "$x[$index]\t$input\t$stdev\r\n";
                    fwrite($file,$writestring);
                    $pt_cnt++; // increment number of points in file
                }
                $index++;
            }
            if ($pt_cnt == 0) {
                // write dummy point if no data is flagged so plots will work
                $writestring = "$x[0]\t10000\t10000\r\n";
                fwrite($file,$writestring);
            }
        }
    }
?>
