#include <stdio.h>
#include <string.h>
#include <math.h>
#include "iniparser.h"  /* includes dictionary.h */
#include "constants.h"
#include "utilities.h"
#include "getarrays.h"

extern int DEBUGGING;

int ReadCopolFile(SCANDATA *currentscan, dictionary *scan_file_dict) {
//Read the farfield listing and fill the dynamic arrays for the current scan

    FILE *fileptr;
    int narg, startrow;
    float az, el, amp, phase, stepsize;
    long int i;
    char *ptr;
    char buf[500];
    char *delimiter;
    long int arrayindex;
    char printmsg[200];
    
    if (DEBUGGING) {
        fprintf(stderr,"Enter ReadCopolFile.\n");
    }

    //If comma isn't specified, delimiter is "\t" regardless of what is in input file:
    delimiter = iniparser_getstring(scan_file_dict, "settings:delimiter", "\t");    
    if (strcmp(delimiter,",")) {
        strcpy(delimiter,"\t");
    }

    // k is wavenumber.  2 * pi * frequency / c   TODO: move this to where the SCANDATA is loaded.
    currentscan -> k = 2 * PI * currentscan -> f / (c_mm_per_ns / 1000.0);

    // open the input listing file:
    fileptr = fopen(currentscan -> ff, "r");
    if (fileptr == NULL) {
        sprintf(printmsg, "ERROR: ReadCopolFile(): Could not open file = %s\n" , currentscan -> ff);
        PRINT_STDOUT(printmsg);
        exit(ERR_COULD_NOT_OPEN_FILE);
        return(-1);
    }
    
    // allocate space for arrays, if file has opened
    if (SCANDATA_allocateArrays(currentscan) != 0) {
        sprintf(printmsg, "ERROR: ReadCopolFile(): Could not allocate enough memory.\n");
        PRINT_STDOUT(printmsg);
        exit(ERR_OUT_OF_MEMORY);
        return(-1);
    }
    
    // skip the header 
    for (i = 1; i < currentscan -> ff_startrow; i++) {
        ptr = fgets(buf, sizeof(buf), fileptr);
        if (i == 70) {
            // TODO:  What's this?
            sprintf(currentscan -> notes,"'%s'",buf);
        }
    }
    
    //Fill up the arrays
    arrayindex=0;
    do {
        ptr = fgets(buf, sizeof(buf), fileptr);

        if (ptr == NULL) break;
        
        narg = 0;
        if (!strcmp(delimiter,",")) {      
            narg = sscanf(ptr,"%f,%f,%f,%f", &az, &el, &amp, &phase);
        } else if (!strcmp(delimiter,"\t")) {   
            narg = sscanf(ptr,"%f\t%f\t%f\t%f", &az, &el, &amp, &phase);   
        }
        if (narg != 4) {
            sprintf(printmsg,"Error parsing line %d, %s\n",i,currentscan -> ff);
            PRINT_STDOUT(printmsg);
            exit(ERR_LINE_FORMAT_PROBLEM);
        }

        az *= currentscan -> sideband_flipped;
        el *= currentscan -> sideband_flipped;
        phase *= currentscan -> sideband_flipped;
        currentscan -> ff_az[arrayindex] = az;
        currentscan -> ff_el[arrayindex] = el;
        currentscan -> ff_amp_db[arrayindex] = amp;
        currentscan -> ff_phase_deg[arrayindex] = phase;
        currentscan -> ff_phase_rad[arrayindex] = phase * PI / 180.0;
        currentscan -> x[arrayindex] = az - currentscan -> az_nominal;
        currentscan -> y[arrayindex] = el - currentscan -> el_nominal;
        currentscan -> radius[arrayindex] = sqrt(pow(currentscan -> x[arrayindex], 2.0) + pow(currentscan -> y[arrayindex], 2.0));
        currentscan -> radius_squared[arrayindex] = pow(currentscan -> radius[arrayindex], 2.0);

        if (DEBUGGING && arrayindex == 0) {
            printf("first line: %s",buf);
        }
        arrayindex++;   
            
    } while (1);
    
    if (DEBUGGING) {
        printf("ReadCopolFile(): last line: %s",buf);
    }
    
    fclose(fileptr);

    // compute and store the angular step size in the scan file:
    stepsize = fabs(currentscan -> ff_az[1] - currentscan -> ff_az[0]);
    if (stepsize == 0) {
        //In case listing is in vertical strips rather horizontal
        stepsize = fabs(currentscan -> ff_el[1] - currentscan -> ff_el[0]);
    }
    if (DEBUGGING) {
        printf("ff_stepsize=%f\n",stepsize);
    }
    currentscan -> ff_stepsize = stepsize;

    // Compute the sums and other metrics for the standard subreflector size:
    SCANDATA_computeSums(currentscan, subreflector_radius);

    // Read the copol nearfield listing file:
    ReadFile_NF(currentscan, scan_file_dict, "copol", delimiter);
    return 1;
}

int ReadCrosspolFile(SCANDATA *crosspolscan, SCANDATA *copolscan, dictionary *scan_file_dict) {
//Read the farfield listing and fill the dynamic arrays for the current scan
    
    FILE *fileptr;
    int narg, startrow;
    float az, el, amp, phase;
    long int i;
    char *ptr;
    char buf[500];
    char *delimiter;
    long int arrayindex;
    float maxamp_xpol;
    
    float ifatten_difference;
    char printmsg[200];
    
    if (DEBUGGING) {
        fprintf(stderr,"Enter ReadCrosspolFile.\n");
    }

    //If comma isn't specified, delimiter is "\t" regardless of what is in input file:
    delimiter = iniparser_getstring(scan_file_dict, "settings:delimiter", "\t");    
    if (strcmp(delimiter,",")) {
        strcpy(delimiter,"\t");
    }

    //Get IF attenuation difference between copol, crosspol
    crosspolscan -> ifatten_difference = copolscan -> ifatten - crosspolscan -> ifatten;

    //Assume same number of points as copol listing
    crosspolscan -> ff_pts = copolscan -> ff_pts;
    crosspolscan -> nf_pts = copolscan -> nf_pts;
    
    // open the input listing file:
    fileptr = fopen(crosspolscan -> ff,"r");
    if (fileptr == NULL) {
        sprintf(printmsg, "ReadCrosspolFile(): Could not open file = %s\n", crosspolscan -> ff);
        PRINT_STDOUT(printmsg);
        exit(ERR_COULD_NOT_OPEN_FILE);
        return(-1);
    }

    if (DEBUGGING) {
        fprintf(stderr,"Enter SCANDATA_allocateArraysXpol.\n");
    }

    // reallocate the crosspol farfield arrays with the correct size, from copolscan above:
    SCANDATA_allocateArraysXpol(crosspolscan);

    if (DEBUGGING) {
        printf("skip the header\n");
    }

    // skip the header 
    for (i = 1;  i < crosspolscan -> ff_startrow; i++) {
        ptr = fgets(buf, sizeof(buf), fileptr);
        if (i == 70) {
            // TODO:  What's this?
            sprintf(crosspolscan -> notes,"'%s'",buf);
        }
    }

    if (DEBUGGING) {
        printf("Fill up the arrays\n");
    }

    //Fill up the arrays
    maxamp_xpol=-300.0;
    arrayindex=0;
    do {
        ptr = fgets(buf, sizeof(buf), fileptr);
        if (ptr == NULL) break;

        narg = 0;        
        if(!strcmp(delimiter,",")) {      
             narg = sscanf(ptr,"%f,%f,%f,%f",&az,&el,&amp,&phase);
        } else if (!strcmp(delimiter,"\t")) {   
             narg = sscanf(ptr,"%f\t%f\t%f\t%f",&az,&el,&amp,&phase);   
        }
        if (narg != 4) {
            sprintf(printmsg,"Error parsing line %d, %s\n",i, crosspolscan -> ff);
            PRINT_STDOUT(printmsg);
            exit(ERR_LINE_FORMAT_PROBLEM);
        }

        amp -= crosspolscan -> ifatten_difference;
        az *= crosspolscan -> sideband_flipped;
        el *= crosspolscan -> sideband_flipped;
        phase *= crosspolscan -> sideband_flipped;
        crosspolscan -> ff_az[arrayindex] = az;
        crosspolscan -> ff_el[arrayindex] = el;
        crosspolscan -> ff_amp_db[arrayindex] = amp;
        crosspolscan -> ff_phase_deg[arrayindex] = phase;
        if (amp > maxamp_xpol) {
            maxamp_xpol = amp;
        }

        if (DEBUGGING && arrayindex == 0) {
            printf("CROSSPOL....\nfirst line: %s", buf);
        }
        arrayindex++;   
    
    } while (1);

    if(DEBUGGING) {
        printf("ReadCrosspolFile(): last line: %s",buf);
    }
    
    fclose(fileptr);

    if(DEBUGGING) {
        printf("SCANDATA_computeCrosspolSums(crosspolscan, copolscan)\n");
    }

    SCANDATA_computeCrosspolSums(crosspolscan, copolscan);

    if(DEBUGGING) {
        printf("ReadFile_NF(crosspolscan, scan_file_dict, \"xpol\", delimiter)\n");
    }

    ReadFile_NF(crosspolscan, scan_file_dict, "xpol", delimiter);

    crosspolscan -> max_ff_amp_db = maxamp_xpol;
    crosspolscan -> max_dbdifference = fabs(crosspolscan -> max_ff_amp_db - copolscan -> max_ff_amp_db);
    crosspolscan -> max_dbdifference_nf = fabs(crosspolscan -> max_nf_amp_db - copolscan -> max_nf_amp_db);

    if(DEBUGGING) {
        printf("Exiting ReadCrosspolFile() \n");
    }

    return 1;
}

int ReadFile_NF(SCANDATA *currentscan, dictionary *scan_file_dict, char *scantype, char *delimiter) {
    //Read the nearfield listing and fill the dynamic arrays
    //for the current scan
    FILE *fileptrnf;
    int narg, startrow;
    float az, el, amp,phase;
    long int i;
    char *ptr;
    char buf[500];
    long int arrayindex;
    int once=1;
    float maxamp,stepsize;
  
    float minus_amp_amount = 0.0;
    char printmsg[200];

    fileptrnf = fopen(currentscan -> nf,"r");
    if (fileptrnf == NULL) {
        sprintf(printmsg,"getarrays(NF): Could not open file = %s\n",currentscan -> nf);
        PRINT_STDOUT(printmsg);
        exit(ERR_COULD_NOT_OPEN_FILE);
        return(-1);
    }

    // skip the header 
    for (i=1; i<currentscan -> nf_startrow; i++) {
        ptr = fgets(buf,sizeof(buf),fileptrnf);
    }
    
    maxamp=-300.0;
    //Fill up the arrays
    arrayindex=0;
    int xpts_found = 0;
    int ypts_found = 0;

    float tempy;
    float tempx;

    currentscan -> nf_xpts = 0;
    currentscan -> nf_ypts = 0;
 
    
    if (scantype == "xpol") {
       minus_amp_amount = currentscan -> ifatten_difference;
    }
    do {
        ptr = fgets(buf,sizeof(buf),fileptrnf);

        if (ptr == NULL)
            break;

        if(!strcmp(delimiter,",")) {
             narg = sscanf(ptr,"%f,%f,%f,%f",&az,&el,&amp,&phase);
        }
        if(!strcmp(delimiter,"\t")) {
             narg = sscanf(ptr,"%f\t%f\t%f\t%f",&az,&el,&amp,&phase);
        }
        amp -= minus_amp_amount;
        az*=currentscan -> sideband_flipped;
        el*=currentscan -> sideband_flipped;
        phase*=currentscan -> sideband_flipped;
        currentscan -> nf_x[arrayindex]=az;
        currentscan -> nf_y[arrayindex]=el;

        if (arrayindex == 0) {
            tempy = currentscan -> nf_y[0];
            tempx = currentscan -> nf_x[0];
        }

        if (arrayindex != 0) {
            if (ypts_found == 1) {
                if (currentscan -> nf_y[arrayindex] == tempy) {
                     if (currentscan -> nf_y[arrayindex] != currentscan -> nf_y[arrayindex + 1]) {
                         currentscan -> nf_xpts += 1;
                         tempy = currentscan -> nf_y[arrayindex + 1];
                    }
                }
            }
        }

        //Get nf_xpts, nf_ypts
        if (arrayindex != 0) {
          if (currentscan -> nf_y[arrayindex] == tempy) {
             if (currentscan -> nf_y[arrayindex] != currentscan -> nf_y[arrayindex + 1]) {
                 if (ypts_found == 0) {
                     currentscan -> nf_ypts = arrayindex;
                     //printf("nf_ypts: %d\n",currentscan -> nf_ypts);

                     //getchar();
                     ypts_found = 1;
                     currentscan -> nf_xpts += 1;
                     tempy = currentscan -> nf_y[arrayindex + 1];
                 }
             }
          }
        }

        arrayindex++;
        currentscan -> nf_amp_db[arrayindex]=amp;
        currentscan -> nf_phase_deg[arrayindex]=phase;
        if (amp>maxamp) {
           maxamp=amp;
        }
        if(DEBUGGING) {
            if (once == 1) {
                printf("first line: %s",buf);
                once=-1;
            }
        }
        if (narg != 4) {
            sprintf(printmsg,"Error parsing line %d, %s\n",i,currentscan -> nf);
            PRINT_STDOUT(printmsg);
            exit(ERR_LINE_FORMAT_PROBLEM);
        }
            
    } while (1);
    if(DEBUGGING) {
        printf("getarrays(2): last line: %s",buf);
    }

    stepsize=fabs(currentscan -> nf_x[1]-currentscan -> nf_x[0]);
    if (stepsize == 0) {
       //In case listing is in vertical strips rather horizontal
       stepsize = fabs(currentscan -> nf_x[1] - currentscan -> nf_x[0]);
    }
    
    currentscan -> nf_stepsize = stepsize;
    currentscan -> max_nf_amp_db = maxamp;
    fclose(fileptrnf);

    sprintf(printmsg,"nf_xpts: %d\n",currentscan -> nf_xpts);
    PRINT_STDOUT(printmsg);
    sprintf(printmsg,"nf_ypts: %d\n",currentscan -> nf_ypts);
    PRINT_STDOUT(printmsg);  
    return 1;
}
