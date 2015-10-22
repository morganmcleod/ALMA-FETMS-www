#include "constants.h"

char *VersionNumber = "1.2.1";
/* 1.2.1:  MM add accumulating sum and sumsq maskE on xpol scan.  fix too-small char buffers in SCANDATA.
 * 1.2.0:  MM calculating polarization efficiency using powers on subreflector instead of total powers.
 * 1.1.3:  Removed hard-coded path to stdoutput.txt and stderr.txt.  Now uses output dir for plots.
 * 1.1.2:  MM fixed "set label...screen" commands to gnuplot.
 * 1.1.1:  MM refactoring and commenting the code, cleaning up unused code and data.
 * 1.0.9:  MM code cleanup and adjustments to phase fit and squint algorithms.
 * 1.0.8:  MM fixed nominal pointing angle for band 10.
 *
 */

float c = 2.99792458e8; // m/s
float c_mm_per_ns = 299.79; // c in mm/ns.
float PI = 3.14159265358979323846;
float subreflector_radius = 3.57633437; // degrees
