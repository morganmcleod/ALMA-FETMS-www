#include <stdio.h>
#include <string.h>
#include <math.h>
#include "iniparser.h"
#include "utilities.h"
#include "efficiency.h"
#include "pointingangles.h"
#include "fitphase.h"
#include "fitamplitude.h"
#include "getarrays.h"
#include "plotting_copol.h"
#include "plotting_crosspol.h"
#include "outputfilefunctions.h"
#include "constants.h"

extern int DEBUGGING;

int GetEfficiencies(dictionary *scan_file_dict, int scanset, char *outputfilename) {
    SCANDATA scans[5];
    int num_scans_in_file, i, pol, sb;
    char *sectionName_p;    // section name returned from ini file library
    char sectionname[20];
    char sectiontemp[20];
    char scantype[20];      // 'copol' or 'xpol'
    char delimiter[2];      // '\t' or ','
    char centers[10];       // 'nominal', 'actual', or '7meter'
    char ibuf[5];
    char sectionName_pkey[200];
    float subreflector_radius = subreflector_radius12m;
    int ACA7meter = 0;
    float lambda;

    if (DEBUGGING) {
        fprintf(stderr,"Enter GetEfficiencies.\n");
    }
    
    SCANDATA_init(scans + 0);
    SCANDATA_init(scans + 1);
    SCANDATA_init(scans + 2);
    SCANDATA_init(scans + 3);
    SCANDATA_init(scans + 4);

    if (DEBUGGING) {
        fprintf(stderr,"Enter GetNumberOfScans.\n");
    }

    num_scans_in_file = GetNumberOfScans(scan_file_dict);

    strcpy(delimiter, iniparser_getstring (scan_file_dict, "settings:delimiter", "\t"));
    //If comma isn't specified, delimiter is "\t" regardless of
    //what is in input file
    if(strcmp(delimiter,",")) {
        strcpy(delimiter,"\t");
    }

    strcpy(centers, iniparser_getstring (scan_file_dict, "settings:centers", "nominal"));
    if(!strcmp(centers, "7meter")) {
        subreflector_radius = subreflector_radius7m;
        ACA7meter = 1;
    }

    if (DEBUGGING) {
        fprintf(stderr,"Loop to read sections...\n");
    }

    for(i=0; i<iniparser_getnsec(scan_file_dict); i++) {
        sectionName_p = iniparser_getsecname(scan_file_dict,i);
        sprintf(sectionName_pkey,"%s:scanset",sectionName_p);

        if (iniparser_getint (scan_file_dict, sectionName_pkey, -1) != -1) {
            //Fill up array of scans
            //scans[1] = copol, pol 0
            //scans[2] = xpol, pol 0
            //scans[3] = copol, pol 1
            //scans[4] = xpol, pol 1
            strcpy(sectionname,sectionName_p);
            strcpy(sectiontemp,sectionName_p);

            //if the current scan is in the current scanset
            if (iniparser_getint (scan_file_dict, strcat(sectionname,":scanset"), 0) == scanset) {
                //Get pol for current scan
                strcpy(sectionname,sectiontemp);
                strcat(sectionname,":pol");
                pol = iniparser_getint (scan_file_dict, sectionname, -1);
                //Get type of current scan
                strcpy(sectionname,sectiontemp);
                strcat(sectionname,":type");
                strcpy(scantype,iniparser_getstring (scan_file_dict, sectionname, "null"));

                strcpy(sectionname,sectiontemp);
           
                //Fill in the correct array slot in "scans"
                if ((pol == 0) && !strcmp(scantype,"copol")){
                    GetScanData(scan_file_dict, sectionname, &scans[1]);
                    beamCenters(&scans[1],"nf",delimiter);
                    beamCenters(&scans[1],"ff",delimiter);
                    PickNominalAngles(scans[1].band,&scans[1].az_nominal,&scans[1].el_nominal, ACA7meter);
                    CheckSideband(&scans[1]);
                }
                if ((pol == 0) && !strcmp(scantype,"xpol")){
                    GetScanData(scan_file_dict, sectionname, &scans[2]);
                }
                if ((pol == 1) && !strcmp(scantype,"copol")){
                    GetScanData(scan_file_dict, sectionname, &scans[3]);
                    beamCenters(&scans[3],"nf",delimiter);
                    beamCenters(&scans[3],"ff",delimiter);
                    PickNominalAngles(scans[3].band,&scans[3].az_nominal,&scans[3].el_nominal, ACA7meter);
                    CheckSideband(&scans[3]);
                }
                if ((pol == 1) && !strcmp(scantype,"xpol")){
                    GetScanData(scan_file_dict, sectionname, &scans[4]);
                }
            }
        } // end reading section
    } //end loop on sections

    if (DEBUGGING) {
        fprintf(stderr,"Done loop to read sections.\n");
    }

    //Crosspol sideband is same as copol
    scans[2].sideband_flipped = scans[1].sideband_flipped;
    scans[4].sideband_flipped = scans[3].sideband_flipped;
    
    if(!strcmp(centers,"actual")){
        //If settings:centers = "actual", then disregard nominal pointing angles,
        //use actual pointing angles for efficiencies instead
        scans[1].az_nominal = scans[1].ff_xcenter;
        scans[1].el_nominal = scans[1].ff_ycenter;    
        scans[3].az_nominal = scans[3].ff_xcenter;
        scans[3].el_nominal = scans[3].ff_ycenter;                                                                        
    }
    // Apply the same assumed beam centers to the xpol scans:
    scans[2].az_nominal = scans[1].az_nominal;
    scans[2].el_nominal = scans[1].el_nominal;  
    scans[4].az_nominal = scans[3].az_nominal;
    scans[4].el_nominal = scans[3].el_nominal; 



    ReadCopolFile(&scans[1], scan_file_dict, subreflector_radius);
    FitPhase(&scans[1]);
    FitAmplitude(&scans[1]);
    ReadCrosspolFile(&scans[2],&scans[1],scan_file_dict); 

	ReadCopolFile(&scans[3],scan_file_dict, subreflector_radius);
    FitPhase(&scans[3]);
    FitAmplitude(&scans[3]);
    ReadCrosspolFile(&scans[4],&scans[3],scan_file_dict); 

/*
    scans[3].squint_arcseconds =
        fabs((sqrt( pow(scans[3].delta_x - scans[1].delta_x, 2.0)
                  + pow(scans[3].delta_y - scans[1].delta_y, 2.0) ) )
            * 2.148);

    lambda = c_mm_per_ns / scans[3].f;   // c in mm/ns.  f in GHz.

    scans[3].squint = (100.0 * scans[3].squint_arcseconds) / (1.15 * lambda * 57.3 * 60 * 60 /12000.0 );
*/

    GetAdditionalEfficiencies(&scans[1], &scans[2], &scans[3], &scans[4], centers);

    if (DEBUGGING) {
        fprintf(stderr,"WriteCopolData()...\n");
    }

    WriteCopolData(scan_file_dict, &scans[1], outputfilename);

    if (DEBUGGING) {
        fprintf(stderr,"PlotCopol()...\n");
    }
    PlotCopol(&scans[1],scan_file_dict);

    WriteCrosspolData(scan_file_dict, &scans[2],outputfilename);
    PlotCrosspol(&scans[2],scan_file_dict);

    WriteCopolData(scan_file_dict, &scans[3],outputfilename);
    PlotCopol(&scans[3],scan_file_dict);

    WriteCrosspolData(scan_file_dict, &scans[4],outputfilename);
    PlotCrosspol(&scans[4],scan_file_dict);

    SCANDATA_free(scans + 0);
    SCANDATA_free(scans + 1);
    SCANDATA_free(scans + 2);
    SCANDATA_free(scans + 3);
    SCANDATA_free(scans + 4);

    return 1;   
}

int GetAdditionalEfficiencies(SCANDATA *copol_pol0, SCANDATA *xpol_pol0,
                              SCANDATA *copol_pol1, SCANDATA *xpol_pol1,
                              char *centers)
{

    float tau=0.25;
    float M=20.0;
    float psi_o=64.0154815383723;
    float psi_m=3.5800849111;
    float lambda;
    float delta;
    float pi=PI;
    float beta;

    if(!strcmp(centers,"7meter")) {
        M=21.775537595;
        psi_o=68.4694425916;
        psi_m=3.5798212165;
    }

    copol_pol0->nominal_z_offset = 0.5 * (copol_pol0->delta_z + copol_pol1->delta_z);
    copol_pol1->nominal_z_offset = copol_pol0->nominal_z_offset;
    copol_pol0->eta_tot_np = copol_pol0->eta_phase * copol_pol0->eta_spillover * copol_pol0->eta_taper;
    copol_pol1->eta_tot_np = copol_pol1->eta_phase * copol_pol1->eta_spillover * copol_pol1->eta_taper;

    // Polarization efficiency, using the 'alternative' definition from R.Hills paper,
    // is the ratio of total copol power to total copol+xpol power, NOT masked for the secondary:
    copol_pol0->eta_pol = copol_pol0->sumsq_E / (copol_pol0->sumsq_E + xpol_pol0->sumsq_E);
    copol_pol1->eta_pol = copol_pol1->sumsq_E / (copol_pol1->sumsq_E + xpol_pol1->sumsq_E);

    // Polarization efficiency, using the TICRA definition,
    // is the ratio of total copol power on the secondary to total copol+xpol power on secondary:
//    copol_pol0->eta_pol = copol_pol0->sumsq_maskE / (copol_pol0->sumsq_maskE + xpol_pol0->sumsq_maskE);
//    copol_pol1->eta_pol = copol_pol1->sumsq_maskE / (copol_pol1->sumsq_maskE + xpol_pol1->sumsq_maskE);

    copol_pol0->eta_tot_nd = copol_pol0->eta_tot_np * copol_pol0->eta_pol;
    copol_pol1->eta_tot_nd = copol_pol1->eta_tot_np * copol_pol1->eta_pol;
    
    copol_pol0->eta_defocus = 1-0.3*pow(                       
                            (copol_pol0->k*(copol_pol0->delta_z - copol_pol0->nominal_z_offset)/1000) *
                            pow(32,-2.0) ,2.0);
    copol_pol1->eta_defocus = 1-0.3*pow(                       
                            (copol_pol1->k*(copol_pol1->delta_z - copol_pol1->nominal_z_offset)/1000) *
                            pow(32,-2.0) ,2.0);   
                            
    copol_pol0->total_aperture_eff = copol_pol0->eta_tot_nd * copol_pol0->eta_defocus; 
    copol_pol1->total_aperture_eff = copol_pol1->eta_tot_nd * copol_pol1->eta_defocus; 

    //pol0
    lambda = c_mm_per_ns / copol_pol0->f;    // c in mm/ns.  f in GHz.
    delta = (copol_pol0->delta_z - 200.0 + 0.0000000000001) / pow(M,2.0) / 0.7197;
    beta = (2*pi/lambda)*delta*(1-cos(psi_o/57.3));

    copol_pol0->defocus_efficiency =
    100*
    (
        pow(tau,2.0)
        *pow(sin(beta/2.0)/(beta/2.0),2.0)
        +(1-pow(tau,2.0))
        *(
            pow(sin(beta/2.0)/(beta/2.0),4.0)
            +4.0/pow(beta,2.0)
            *pow((sin(beta)/beta)-1.0,2.0)
         )
    );
    copol_pol0->shift_from_focus_mm = copol_pol0->delta_z  - 200.0;
    copol_pol0->subreflector_shift_mm = fabs(copol_pol0->shift_from_focus_mm) / pow(M,2.0) / 0.7197;
    copol_pol0->mean_subreflector_shift = copol_pol0->nominal_z_offset / pow(M,2.0) / 0.7197;

    //pol1
    lambda = c_mm_per_ns / copol_pol1->f;    // c in mm/ns.  f in GHz.
    delta = (copol_pol1->delta_z - 200.0 + 0.0000000000001) / pow(M,2.0) / 0.7197;
    beta = (2*pi/lambda)*delta*(1-cos(psi_o/57.3));
    
    copol_pol1->defocus_efficiency =
    100*
    (
        pow(tau,2.0)
        *pow(sin(beta/2.0)/(beta/2.0),2.0)
        +(1-pow(tau,2.0))
        *(
            pow(sin(beta/2.0)/(beta/2.0),4.0)
            +4.0/pow(beta,2.0)
            *pow((sin(beta)/beta)-1.0,2.0)
         )
    );
    copol_pol1->shift_from_focus_mm = copol_pol1->delta_z - 200.0;
    copol_pol1->subreflector_shift_mm = fabs(copol_pol1->shift_from_focus_mm) / pow(M,2.0) / 0.7197;
    copol_pol1->mean_subreflector_shift = copol_pol1->nominal_z_offset / pow(M,2.0) / 0.7197;

    //Note: 0.7197 comes from equation 22 of ALMA MEMO 456 using M=20 and phi_0 = 3.58

    return 1;                                    
}
