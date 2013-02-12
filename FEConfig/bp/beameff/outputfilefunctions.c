#include <stdio.h>
#include <string.h>
#include "iniparser.h"
#include "utilities.h"
#include "outputfilefunctions.h"
extern int DEBUGGING;
extern char *VersionNumber;

int WriteCopolData(dictionary *scan_file_dict, SCANDATA *currentscan, char *outputfilename){
    char writeval[200];
    sprintf(writeval,"%f", currentscan->eta_spillover);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "eta_spillover", writeval);
    sprintf(writeval,"%f", currentscan->eta_taper);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "eta_taper", writeval);
    sprintf(writeval,"%f", currentscan->eta_illumination);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "eta_illumination", writeval);
    sprintf(writeval,"%f", currentscan->ff_xcenter);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "ff_xcenter", writeval);
    sprintf(writeval,"%f", currentscan->ff_ycenter);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "ff_ycenter", writeval);
    sprintf(writeval,"%f", currentscan->az_nominal);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "az_nominal", writeval);
    sprintf(writeval,"%f", currentscan->el_nominal);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "el_nominal", writeval);
    sprintf(writeval,"%f", currentscan->nf_xcenter);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "nf_xcenter", writeval);
    sprintf(writeval,"%f", currentscan->nf_ycenter);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "nf_ycenter", writeval);
    sprintf(writeval,"%f", currentscan->max_ff_amp_db);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "max_ff_amp_db", writeval);
    sprintf(writeval,"%f", currentscan->max_nf_amp_db);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "max_nf_amp_db", writeval);  
    sprintf(writeval,"%f", currentscan->delta_x);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "delta_x", writeval);
    sprintf(writeval,"%f", currentscan->delta_y);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "delta_y", writeval);
    sprintf(writeval,"%f", currentscan->delta_z);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "delta_z", writeval);
    sprintf(writeval,"%f", currentscan->eta_phase);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "eta_phase", writeval);
    sprintf(writeval,"%f", currentscan->ampfit_amp);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "ampfit_amp", writeval);
    sprintf(writeval,"%f", currentscan->ampfit_width_deg);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "ampfit_width_deg", writeval);
    sprintf(writeval,"%f", currentscan->ampfit_u_off_deg);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "ampfit_u_off_deg", writeval);
    sprintf(writeval,"%f", currentscan->ampfit_v_off_deg);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "ampfit_v_off_deg", writeval);
    sprintf(writeval,"%f", currentscan->ampfit_d_0_90);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "ampfit_d_0_90", writeval);
    sprintf(writeval,"%f", currentscan->ampfit_d_45_135);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "ampfit_d_45_135", writeval);
    sprintf(writeval,"%f", currentscan->edge_dB);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "edge_dB", writeval);
    sprintf(writeval,"%f", currentscan->nominal_z_offset);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "nominal_z_offset", writeval);
    sprintf(writeval,"%f", currentscan->eta_tot_np);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "eta_tot_np", writeval);
    sprintf(writeval,"%f", currentscan->eta_pol);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "eta_pol", writeval);
    sprintf(writeval,"%f", currentscan->eta_tot_nd);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "eta_tot_nd", writeval);
    sprintf(writeval,"%f", 100.0 * currentscan->eta_defocus);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "defocus_efficiency", writeval);
    sprintf(writeval,"%f", currentscan->total_aperture_eff);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "total_aperture_eff", writeval);
    sprintf(writeval,"%f", currentscan->shift_from_focus_mm);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "shift_from_focus_mm", writeval);
    sprintf(writeval,"%f", currentscan->subreflector_shift_mm);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "subreflector_shift_mm", writeval);
    sprintf(writeval,"%f", currentscan->defocus_efficiency);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "defocus_efficiency_due_to_moving_the_subreflector", writeval);
    sprintf(writeval,"%s", currentscan->notes);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "notes", writeval);
    sprintf(writeval,"%f", currentscan->mean_subreflector_shift);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "mean_subreflector_shift", writeval);
    sprintf(writeval,"%s", currentscan->is4545_scan);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "4545_scan", writeval);
    
    
    if (currentscan->pol == 1){
        sprintf(writeval,"%f", currentscan->squint);
        UpdateDictionary(scan_file_dict,currentscan->sectionname, "squint", writeval);
        sprintf(writeval,"%f", currentscan->squint_arcseconds);
        UpdateDictionary(scan_file_dict,currentscan->sectionname, "squint_arcseconds", writeval);
    }
 return 1;   
}

int WriteCrosspolData(dictionary *scan_file_dict, SCANDATA *currentscan, char *outputfilename){
    char writeval[200];
    sprintf(writeval,"%f", currentscan->eta_spill_co_cross);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "eta_spill_co_cross", writeval);
    sprintf(writeval,"%f", currentscan->eta_pol_on_secondary);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "eta_pol_on_secondary", writeval);
    sprintf(writeval,"%f", currentscan->eta_pol_spill);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "eta_pol_spill", writeval);
    sprintf(writeval,"%s", currentscan->notes);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "notes", writeval);
    //sprintf(writeval,"%f", currentscan->eta_total_nofocus);
    //UpdateDictionary(scan_file_dict,currentscan->sectionname, "eta_total_nofocus", writeval);
    sprintf(writeval,"%f", currentscan->max_ff_amp_db);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "max_ff_amp_db", writeval);
    sprintf(writeval,"%f", currentscan->max_dbdifference);
    UpdateDictionary(scan_file_dict,currentscan->sectionname, "max_dbdifference", writeval);
    if (DEBUGGING) {
      fprintf(stderr,"Done max_dbdifference = %f (ptr=%p)\n",currentscan->max_dbdifference,
	      &(currentscan->max_dbdifference));
    }

 return 1;                            
}

int GetOutputFilename(dictionary *scan_file_dict,char outname[400]) {
    char outfiletemp[400];
    char *outputfilename;
    sprintf(outname,"%soutput.txt",iniparser_getstring (scan_file_dict, "settings:outputdirectory", "null"));
    return 1;
}

int SaveOutputFile(dictionary *scan_file_dict, char *outputfilename){
    FILE *outfileptr;
    char writeval[20];
    	
    outfileptr = fopen(outputfilename,"w");
    sprintf(writeval,"%s", VersionNumber);
    UpdateDictionary(scan_file_dict,"settings", "software_version", writeval);
    iniparser_dump_ini(scan_file_dict,outfileptr);
    fclose(outfileptr);
    return 1;
}

int UpdateDictionary(dictionary *scan_file_dict, char *sectionname, char *keyname, char *writeval){
    char section_key[200];

    strcpy(section_key,sectionname);
    strcat(section_key,":");
    strcat(section_key,keyname);
    iniparser_setstring(scan_file_dict,section_key,writeval);
    return 1;
}


