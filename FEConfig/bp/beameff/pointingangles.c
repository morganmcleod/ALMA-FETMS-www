#include <stdio.h>
#include <string.h>
#include <math.h>
extern int DEBUGGING;
#include "iniparser.h"
#include "utilities.h"
#include "pointingangles.h"
                    
int beamCenters(SCANDATA *currentscan, char *listingtype, char *delimiter) {                        
                                     
    FILE *fileptr;
    int narg, startrow;
    long int i, npts, number_of_points;
    char *ptr, *filename, scanstringtemp[10];
    const char *scanstring;
    char buf[500];
    float az_temp,el_temp, amp_temp, amplitude_max;
    float threshold;
    float SumCtrAreaAZ,SumCtrAreaEL,SumAmpCofA,tempAmpCofA;
    float SumCtrMassAZ,SumCtrMassEL,SumAmpCofM,tempAmpCofM;
    float X_CenterOfMass, Y_CenterOfMass,X_CenterOfArea, Y_CenterOfArea;
    
    //Check if nearfield or farfield
    if (!(strcmp("nf",listingtype))){
       filename=currentscan->nf;
       startrow = currentscan->nf_startrow;
    }
    else{
       filename=currentscan->ff; 
       startrow = currentscan->ff_startrow;
    }

    /* open the file and count the number of data rows */
    npts = 0;
    fileptr = fopen(filename,"r");
    
    if (fileptr == NULL) {
        printf("pointingangles(1): Could not open file = %s\r\n",filename);
        getchar();
        exit(ERR_COULD_NOT_OPEN_FILE);
        return(-1);
    }
  
    /* skip the header */
    for (i=0; i<startrow; i++) {
        ptr = fgets(buf,sizeof(buf),fileptr);
    }    
   
    do {
        ptr = fgets(buf,sizeof(buf),fileptr);
        if (ptr == NULL) break;
        npts++;
    } while (1);
    npts++; /* start data at 1 instead of 0 */
    fclose(fileptr);
    number_of_points = npts;
    
    /*
    printf("npts= %d\r\n",number_of_points);
    getchar();
    */

    
    SumCtrAreaAZ = 0.0;
    SumCtrAreaEL = 0.0;
    SumAmpCofA = 0.0;
    tempAmpCofA = 0.0;
    SumCtrMassAZ = 0.0;
    SumCtrMassEL = 0.0;
    SumAmpCofM = 0.0;
    tempAmpCofM = 0.0;
    
    /*
    strcpy(scanstringtemp,"%f");
    strcat(scanstringtemp,delimiter);
    strcat(scanstringtemp,"%f");
    strcat(scanstringtemp,delimiter);
    strcat(scanstringtemp,"%f");
    scanstring=scanstringtemp;
    
    printf("scanstring= %s\r\n",scanstring);
    getchar();
   */


    int once = 1;
    /********************
    / Get max amplitude
    *********************/
    fileptr = fopen(filename,"r");
    if (fileptr == NULL) {
        printf("pointingangles(2): Could not open file = %s\r\n",filename);
        return(-1);
    }
    npts = 0;
    // skip the header 
    for (i=1; i<startrow; i++) {
        ptr = fgets(buf,sizeof(buf),fileptr);
    }
    // set this to a small value so that it can be computed as we read the data 
    amplitude_max = -300.0;
    do {
        ptr = fgets(buf,sizeof(buf),fileptr);
        
        
        if (ptr == NULL) break;
            npts++;
            if(!strcmp(delimiter,",")){      
               narg = sscanf(ptr,"%f,%f,%f",&az_temp,&el_temp,&amp_temp);
            }
            if(!strcmp(delimiter,"\t")){ 
               narg = sscanf(ptr,"%f\t%f\t%f",&az_temp,&el_temp,&amp_temp);  
               
            } 
            
                if(DEBUGGING){
                    if (once == 1){
                        printf("first line: %s",buf);
                        once=-1;
                    } 
                }
        if (narg != 3) {
            printf("Error parsing line %d, %s\r\n",i,filename);
            getchar();
            exit(ERR_LINE_FORMAT_PROBLEM);
        }
            if (amp_temp > amplitude_max) {
            amplitude_max = amp_temp;
        }
    } while (1);
    npts++; // start data at 1 instead of 0 
    if (DEBUGGING){
        printf("pointingangles: last line: %s",buf);
    }
    fclose(fileptr);
    
    //printf("max amp= %f",amplitude_max);
    //getchar();

    threshold = amplitude_max-10.0;
    
    /**************************************
    / Get Center of Area, Center of Mass
    *************************************/
    fileptr = fopen(filename,"r");
    if (fileptr == NULL) {
        printf("pointingangles(3): Could not open file = %s\r\n",filename);
        return(-1);
    }
    npts = 0;
    /* skip the header */
    for (i=0; i<=startrow; i++) {
    ptr = fgets(buf,sizeof(buf),fileptr);
    }
    /* set this to a small value so that it can be computed as we read the data */
    //*amplitude_max = -300.0;
    do {
        ptr = fgets(buf,sizeof(buf),fileptr);
        if (ptr == NULL) break;
        npts++;
            if(!strcmp(delimiter,",")){      
               narg = sscanf(ptr,"%f,%f,%f",&az_temp,&el_temp,&amp_temp);
            }
            else{
               narg = sscanf(ptr,"%f\t%f\t%f",&az_temp,&el_temp,&amp_temp);   
            } 

        if (narg != 3) {
            printf("Error parsing line %d, %s\r\n",npts+=startrow,filename);
            getchar();
            exit(ERR_LINE_FORMAT_PROBLEM);
        }
        if (amp_temp>=threshold){
            tempAmpCofA = pow(10.0,(threshold)/10.0);
            SumCtrAreaAZ += az_temp*tempAmpCofA;
            SumCtrAreaEL += el_temp*tempAmpCofA;
            SumAmpCofA += tempAmpCofA;
           
            tempAmpCofM = pow(10.0,(amp_temp)/10.0);
            SumCtrMassAZ += az_temp*tempAmpCofM;
            SumCtrMassEL += el_temp*tempAmpCofM;
            SumAmpCofM += tempAmpCofM;
        }
        
        X_CenterOfMass = SumCtrMassAZ/SumAmpCofM;
        Y_CenterOfMass = SumCtrMassEL/SumAmpCofM;
        //Calculated, but not used
        X_CenterOfArea = SumCtrAreaAZ/SumAmpCofA;
        Y_CenterOfArea = SumCtrAreaEL/SumAmpCofA;
    } while (1);
    npts++; /* start data at 1 instead of 0 */
    fclose(fileptr);
    
    /*
    printf("Center X: %f\r\n",X_CenterOfMass);
    printf("Center Y: %f\r\n",Y_CenterOfMass);
    getchar();
    */
    
    //Check if nearfield or farfield, assign result values 
    if (!(strcmp("nf",listingtype))){
       currentscan->nf_pts = number_of_points - 1;
       currentscan->nf_xcenter = X_CenterOfMass;
       currentscan->nf_ycenter = Y_CenterOfMass;
       currentscan->max_nf_amp_db = amplitude_max;
 
    }
    else{
       currentscan->ff_pts = number_of_points - 1;
       currentscan->ff_xcenter = X_CenterOfMass;
       currentscan->ff_ycenter = Y_CenterOfMass;
       currentscan->max_ff_amp_db = amplitude_max;
    }
  
    return 1;
}

int RotateScan180(SCANDATA *currentscan){
    printf("Rotating scan...\r\n");
    printf("Band %d",currentscan->band);
    printf("nominal angles: %f, %f\r\n",currentscan->az_nominal,currentscan->el_nominal);
    printf("pointing angles: %f, %f\r\n",currentscan->ff_xcenter,currentscan->ff_ycenter);
    getchar();
    
    long int i;
    for (i=1;i<currentscan->nf_pts;i++){
        currentscan->nf_x[i]=(-1)*currentscan->nf_x[i];
        currentscan->nf_y[i]=(-1)*currentscan->nf_y[i];
    }
    for (i=1;i<currentscan->ff_pts;i++){
        currentscan->ff_az[i]=(-1)*currentscan->ff_az[i];
        currentscan->ff_el[i]=(-1)*currentscan->ff_el[i];
    } 
    //Switch signs for pointing angles
    //currentscan->ff_az = (-1) * currentscan->ff_az;
    //currentscan->ff_el = (-1) * currentscan->ff_el;
    
    return 1;
}

int CheckSideband(SCANDATA *currentscan){
    int az_signs_not_equal,el_signs_not_equal;
    
    az_signs_not_equal = !(((currentscan->az_nominal > 0) - (currentscan->az_nominal < 0)) ==
                         ((currentscan->ff_xcenter > 0) - (currentscan->ff_xcenter < 0)));
                                               
    el_signs_not_equal = !(((currentscan->el_nominal > 0) - (currentscan->el_nominal < 0)) ==
                         ((currentscan->ff_ycenter > 0) - (currentscan->ff_ycenter < 0)));   
                         
    if (currentscan->az_nominal == 0.00){
       az_signs_not_equal = 0;
    }
    if (currentscan->el_nominal == 0.00){
       el_signs_not_equal = 0;
    }

    char printmsg[200];

    sprintf(printmsg, "Band %d: CheckSideband()...\r\n",currentscan->band);
    PRINT_STDOUT(printmsg);
    sprintf(printmsg, "nominal angles: %f, %f\r\n",currentscan->az_nominal,currentscan->el_nominal);
    PRINT_STDOUT(printmsg);
    sprintf(printmsg, "pointing angles: %f, %f\r\n",currentscan->ff_xcenter,currentscan->ff_ycenter);
    PRINT_STDOUT(printmsg);

    //Rotate scan if wrong sideband
    if (az_signs_not_equal && el_signs_not_equal){
        sprintf(printmsg, "Rotating scan... %s\r\n",currentscan->sectionname);
        PRINT_STDOUT(printmsg);

        currentscan->ff_xcenter = (-1.0) * currentscan->ff_xcenter;
        currentscan->ff_ycenter = (-1.0) * currentscan->ff_ycenter;    
        currentscan->nf_xcenter = (-1.0) * currentscan->nf_xcenter;
        currentscan->nf_ycenter = (-1.0) * currentscan->nf_ycenter;    
        currentscan->sideband_flipped = -1.0;                        
    }   
    else{
        currentscan->sideband_flipped = 1.0;   
    }                                         
    return 1;
}
