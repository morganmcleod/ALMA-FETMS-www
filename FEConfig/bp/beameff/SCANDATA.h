#ifndef SCANDATA_H
#define SCANDATA_H

typedef struct SCANDATA_T {
/**
 * SCANDATA is the package of raw data and processed values pertaining mainly
 * to the results from a single beam scan measurement.   This includes both
 * co-pol and cross-pol maps, results, and metadata for both pol0 and pol1.
 *
 * That is, four complete scans are stored.
 *
 * Some of the calculated efficiency results apply to a single scan,
 * some to a co-cross pair for a single pol,
 * and some are calculated results from all four scans.
 *
 * The structure also includes data about the state of the scanner system 
 * at the time when the measurements were taken.
 * 
 */    

// values from input file pertain to a single scan:
    int band;               ///< cartridge band under test
    int scanset;            ///< scanset number this scan is part of, usually 1.
    char type[10];          ///< type of scan "copol" or "xpol"
    int pol;                ///< polarization of scan 0 or 1
    int tilt;               ///< tilt table angle in degrees where 0 is horizon
    int f;                  ///< RF in GHz.  TODO: should this be a float?
    int sb;                 ///< 1=USB, 2=LSB
    float ifatten;          ///< IF processor attenuation in dB for this single scan.
    char nf[200];           ///< filename of nearfield listing txt file
    int nf_startrow;        ///< number of header rows to skip when reading nf
    char ff[200];           ///< filename of farfield listing txt file
    int ff_startrow;        ///< number of header rows to skip when reading ff
    char nf2[200];          ///< 2nd nearfield filename.  Apparently not used.  Used for multi-z scan sets?
    int nf2_startrow;       ///< rows to skip in nf2.     Apparently not used.  Used for multi-z scan sets?
    char ff2[200];          ///< 2nd farfield filename.   Apparently not used.  Used for multi-z scan sets?
    int ff2_startrow;       ///< rows to skip in ff2.     Apparently not used.  Used for multi-z scan sets?
    char sectionname[20];   ///< section name of scan data in input/output txt files
    char notes[200];        ///< operator notes entered when measuring.   Always blank as of class.eff 1.0.19 2012-08-14.
    char datetime[200];     ///< TODO:  seems to duplicate ts and is not used, =-1
    char is4545_scan[10];   ///< "TRUE" or "FALSE"  TODO: not used?
    char scanset_id[10];    ///< keyID from ScanSetDetails passed in for plot label
    char scan_id[10];       ///< keyID from ScanDetails passed in for plot label
    char ts[100];           ///< time and date string when measured
    char fecfg[10];         ///< keyID from FE_Config passed in for plot labels

//values calculated after reading input file
    float ff_xcenter, ff_ycenter;   ///< center of mass of the farfield beam ampltude data
    float nf_xcenter, nf_ycenter;   ///< center of mass of the nearfield beam ampltude data
    float k;                        ///< wavenumber = 2 * pi * frequency / c
    float sideband_flipped;         ///< 1.0 if USB, -1.0 if LSB   TODO: confirm.
    float az_nominal, el_nominal;   ///< nominal ff pointing angle for the band under test

//nearfield scan info:
    //Assume scan is square unless input file says otherwise
    float max_nf_amp_db;    ///< peak nearfield amplitude in input listing
    float nf_stepsize;      ///< meters between nf points.  TODO: buggy, not used.
    int nf_xpts, nf_ypts;   ///< x and y dimensions of nearfield input listing
    long int nf_pts;        ///< number of points in nearfield input listing

//farfield scan info:
    float max_ff_amp_db;    ///< peak farfield amplitude in input listing
    float ff_stepsize;      ///< degrees between ff points.  TODO: not used
    int ff_xpts, ff_ypts;   ///< az and el dimensions of farfield input listing
    long int ff_pts;        ///< number of points in ff input listing

//Dynamic arrays populated from input listing files
    float *ff_az, *ff_el;   ///< farfield az, el angles array from listing
    float *ff_amp_db;       ///< farfield amplitudes from listing
    float *ff_phase_deg;    ///< farfield phases from listing
    float *ff_phase_rad;    ///< farfield phases converted to radians

    float *nf_x, *nf_y;     ///< nearfield x, y positions array from listing
    float *nf_amp_db;       ///< nearfield amplitudes from listing
    float *nf_phase_deg;    ///< nearfield phases from listing
    float *x, *y;           ///< farfield az, el relative to nominal pointing angle

    float *radius;          ///< farfield angle away from nominal pointing angle
    float *radius_squared;  ///< radius ^2

    float *E;               ///< 1-D array containing the magnitude of the voltages from the 2D pattern,
                            ///< normallized so that the copol peak is 1.0.
                            ///< If the scan being read is xpol, then normallized to the copol peak.

    float *mask;            ///< 1-D array containing a value between 0 and 1, inclusive for each point in E.
                            ///< It is 1 for points inside the angle subtended by the subreflector, 0 outside, 
                            ///< and between 0 and 1 for pixels within a narrow annulus of the subreflector edge
                            ///< (whose width is set by the separation of pixels).
                            ///< The latter factor is determined simply by a linear taper. For
                            ///< finely-spaced scans (0.1 deg pixels), using this latter factor will only
                            ///< affect the results at the 0.01% level.  For coarser scans, it would become
                            ///< more important as there would be fewer points inside the subreflector area.

    float *maskE;           ///< Not used?

    float *pow_sec;         ///< 1D array containing the amplitude (i.e. the voltage squared) of the 2D co-pol
                            ///< scan, but truncated in the same manner as maskE.   Not used?
//Sums and sums of squares:
    double sum_mask;        ///< scalar containing the sum of mask
    double sum_E;           ///< scalar containing the sum of E across the whole 2D scan
    double sum_maskE;       ///< scalar containing the same sum of E, but where each point has been truncated
                            ///<  to zero according to the mask.
    double sum_powsec;      ///< scalar containing the sum of the normalized amplitude (E**2) across the 2D scan
    double sum_intensity;   ///< scalar containing the total power in the entire 2D scan
    double sum_intensity_on_subreflector;
                            ///< scalar containing the total power falling on the subreflector

    float sumEdge;          ///< scalar containing the number of pixels falling within a narrow annulus of the subreflector edge
    float sumsq_Edge;       ///< apparently unused
    float sumEdgeE;         ///< scalar containing the sum of E**2 for points within a narrow annulus of the subreflector edge
    float sumsq_EdgeE;      ///< apparently unused

    double sumsq_E;         ///< scalar containing the sum squared of E over the whole 2D scan
    double sumsq_maskE;     ///< scalar containing the sum squared of maskE over the whole 2D scan
    double sumsq_powsec;    ///< scalar containing the sum squared of pow_sec over the whole 2d scan

//Copol efficiency values
    float eta_spillover;    ///< Spillover efficiency value in [0...1]
    float eta_taper;        ///< Taper efficiency in [0...1]
    float eta_illumination; ///< Illumination efficiency in [0...1]

//Crosspol values
    float ifatten_difference;   ///< dB difference in IF attenuation in effect during the measurement (xpol - copol)
    float max_dbdifference;     ///< dB difference between the farfield scans peak power levels (copol - xpol)
    float max_dbdifference_nf;  ///< dB difference between the nearfield scans peak power levels (copol - xpol)

//TICRA Polarization efficiencies
    float eta_spill_co_cross;   ///< component of xpol spillover efficiency?
    float eta_pol_on_secondary; ///< component of xpol spillover efficiency?
    float eta_pol_spill;        ///< xpol spillover efficiency in [0...1]?
    float eta_total_nofocus;    ///< read and written to files but not used?

//Phase-fit and amplitude-fit
    float delta_x, delta_y, delta_z;    ///< Offset in mm of the scan's phase center relative to the location 
                                        ///< of the probe at nominal beam center.

    float eta_phase;            ///< Phase efficiency in [0...1]
    float ampfit_amp;           ///<
    float ampfit_width_deg;     ///<
    float ampfit_u_off_deg;     ///<
    float ampfit_v_off_deg;     ///<
    float ampfit_d_0_90;        ///<
    float ampfit_d_45_135;      ///<

//Plot filenames, apparently not used:
    char *plot_copol_nfamp, *plot_copol_nfphase, *plot_copol_ffamp, *plot_copol_ffphase;
    char *plot_xpol_nfamp, *plot_xpol_nfphase, *plot_xpol_ffamp, *plot_xpol_ffphase;

//Additional efficiency values calculated from both polarizations together:
    float edge_dB;              ///< Average power in dB of the pixels falling at the edge of the subreflector.
    float sum;                  ///< apparenly not used.
    float nominal_z_offset;     ///< average of delta_z for copol and xpol scans
    float eta_tot_np;           ///< Efficiency other than polarizaion and defocus in [0..1]
                                ///<  (eta_phase * eta_spillover * eta_taper)
    float eta_pol;              ///< Polarization efficiency in [0..1]
    float eta_tot_nd;           ///< Efficiency other than defocus in [0..1] (eta_tot_np * eta_pol)
    float eta_defocus;          ///< Defocus efficency in [0..1]
    float total_aperture_eff;   ///< Overall aperture efficiency in [0..1] (eta_tot_nd * eta_defocus)
    float shift_from_focus_mm;  ///< Difference between delta_z and the nominal probe distance of 200 mm.
    float subreflector_shift_mm;    ///< ?
    float squint;               ///< Beam squint as percentage of FWHM of the Beam.   Not calculated by this program?
    float squint_arcseconds;    ///< Beam squint in arcsecods.   Not calculated by this program?
    float defocus_efficiency;   ///< Defocus efficency in [0..1] calculated in GetAdditionalEfficiencies().
                                ///<  How different from eta_defocus?
    float mean_subreflector_shift;  ///< ?

} SCANDATA;

extern void SCANDATA_init(SCANDATA *data);
///< initialize the SCANDATA struct.

extern void SCANDATA_free(SCANDATA *data);
///< tear down the SCANDATA struct and release resources.

extern int SCANDATA_allocateArrays(SCANDATA *data);
///< allocate memory in the dynamic arrays.   Returns zero if successful.

extern int SCANDATA_allocateArraysXpol(SCANDATA *data);
///< allocate memory in the dynamic arrays.   Returns zero if successful.

extern int SCANDATA_computeSums(SCANDATA *data, float maskRadius);
///< compute sums, sums of squares, and other metrics on the ff copol data, using the provided maskRadius

extern int SCANDATA_computeCrosspolSums(SCANDATA *crosspolscan, SCANDATA *copolscan);
///< compute sums, sums of squares, and on the ff xpol data, using peak and mask from the provided copol data set

#endif
