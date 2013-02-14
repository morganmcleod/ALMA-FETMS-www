#include <stdio.h>
#include <string.h>
#include <math.h>
#include "constants.h"
#include "iniparser.h"
#include "utilities.h"
#include "z.h"
extern int DEBUGGING;

int GetZ(dictionary *scan_file_dict){
    int num_sections,i;
    char *sectionname;
    char sectionname_nf[30];
    char sectionname_ff[30];
    char sectionname_nf2[30];
    char sectionname_ff2[30];
    char ZplusOrZminus[6];
    
    //printf("Reading for NSI values...\n");
    num_sections = iniparser_getnsec(scan_file_dict);
    for (i=0;i<num_sections;i++){
        
        sectionname = iniparser_getsecname(scan_file_dict,i);
        if (!strncmp(sectionname,"scan",4)){                       
           sprintf(sectionname_nf,"%s:nf",sectionname);
           sprintf(sectionname_ff,"%s:ff",sectionname);
           sprintf(sectionname_nf2,"%s:nf2",sectionname);
           sprintf(sectionname_ff2,"%s:ff2",sectionname);

           //Check if beam2 listings are present
           if (iniparser_getstring(scan_file_dict,sectionname_nf2,"null") != "null"){
              GetZplusOrZminus(scan_file_dict, sectionname, "nf", ZplusOrZminus);
              CreateZlisting(scan_file_dict, sectionname_nf, sectionname_nf2, ZplusOrZminus);                                                     
           }
           if (iniparser_getstring(scan_file_dict,sectionname_ff2,"null") != "null"){ 
              GetZplusOrZminus(scan_file_dict, sectionname, "ff", ZplusOrZminus); 
              CreateZlisting(scan_file_dict, sectionname_ff, sectionname_ff2, ZplusOrZminus);                                                    
           }
        }
    }     
    return 1;
}

int GetZplusOrZminus(dictionary *scan_file_dict, char *sectionname, char *nf_or_ff, char result[]){
    char sectionname_z1[30];
    char sectionname_z2[30];
    char sectionname_z1startrow[30];
    char sectionname_z2startrow[30];
    char *z1_filename, *z2_filename;
    int z1_startrow;
    int z2_startrow;
    int i;
    char *ptr;
    float maxamp1,maxamp2,maxphase1,maxphase2;
    FILE *fileptrZ1, *fileptrZ2;
    char buf[500];
    char *delimiter;
    float az,el,amp,phase;
    int narg;
    char printmsg[200];
    float z1real,z1imag,z2real,z2imag;
    float intensity1,intensity2;
    float magZPLUS,magZMINUS;
    int maxamp_index = 0;
    int count = 0;
    
    delimiter=iniparser_getstring (scan_file_dict,"settings:delimiter", "\t");
    maxamp1=-900;
    maxamp2=-900;
    maxphase1=-900;
    maxphase2=-900;
    sprintf(sectionname_z1,"%s:%s",sectionname,nf_or_ff);
    sprintf(sectionname_z2,"%s:%s2",sectionname,nf_or_ff);
    sprintf(sectionname_z1startrow,"%s:%s_startrow",sectionname,nf_or_ff);
    sprintf(sectionname_z2startrow,"%s:%s2_startrow",sectionname,nf_or_ff);
    z1_filename = iniparser_getstring(scan_file_dict,sectionname_z1,"null");
    z2_filename = iniparser_getstring(scan_file_dict,sectionname_z2,"null");
    z1_startrow = iniparser_getint(scan_file_dict,sectionname_z1startrow,0);
    z2_startrow = iniparser_getint(scan_file_dict,sectionname_z2startrow,0);
    
    //Open z1 file, get max amp, get phase at max amp
    // skip the header 
    fileptrZ1 = fopen(z1_filename,"r");
    for (i=1; i<z1_startrow; i++) {
        ptr = fgets(buf,sizeof(buf),fileptrZ1);
        
    }
    do {
        count++;
        ptr = fgets(buf,sizeof(buf),fileptrZ1);

        if (ptr == NULL) break;
            //if(!strcmp(delimiter,",")){      
               //  narg = sscanf(ptr,"%f,%f,%f,%f",&az,&el,&amp,&phase);
            //}
            //if(!strcmp(delimiter,"\t")){  
                 narg = sscanf(ptr,"%f\t%f\t%f\t%f",&az,&el,&amp,&phase);  
           //} 
            
            if (amp > maxamp1){
               maxamp1 = amp;
               maxphase1 = phase;
               maxamp_index = count;
            }
        if (narg != 4) {
            sprintf(printmsg,"Error parsing line %d\n",i);
            PRINT_STDOUT(printmsg);
            getchar();
            exit(ERR_LINE_FORMAT_PROBLEM);
        }
            
    } while (1);
    fclose(fileptrZ1);
    
    
    //Open z2 file, get max amp, get phase at max amp
    // skip the header 
    fileptrZ2 = fopen(z2_filename,"r");
    count = 0;
    for (i=1; i<z2_startrow; i++) {
        ptr = fgets(buf,sizeof(buf),fileptrZ2);
    }
    do {
        count++;
        ptr = fgets(buf,sizeof(buf),fileptrZ2);

        if (ptr == NULL) break;
            //if(!strcmp(delimiter,",")){      
                 //narg = sscanf(ptr,"%f,%f,%f,%f",&az,&el,&amp,&phase);
            //}
            //if(!strcmp(delimiter,"\t")){  
                 narg = sscanf(ptr,"%f\t%f\t%f\t%f",&az,&el,&amp,&phase);  
            //} 
            
            if (count==maxamp_index){
               maxamp2 = amp;
               maxphase2 = phase;
            }

            

        if (narg != 4) {
            sprintf(printmsg,"Error parsing line %d\n",i);
            PRINT_STDOUT(printmsg);
            exit(ERR_LINE_FORMAT_PROBLEM);
        }
            
    } while (1);
    fclose(fileptrZ2);
    intensity1 = pow(10.0,(maxamp1/20.0));
    z1real = intensity1 * cos(PI * maxphase1/180.0);
    z1imag = intensity1 * sin(PI * maxphase1/180.0);
    intensity2 = pow(10.0,(maxamp2/20.0));
    z2real = intensity2 * cos(PI * maxphase2/180.0);
    z2imag = intensity2 * sin(PI * maxphase2/180.0);
    magZPLUS = sqrt(pow(z1real-z2imag,2.0) + pow(z1imag+z1real,2.0));
    magZMINUS = sqrt(pow(z1real+z2imag,2.0) + pow(z1imag-z2real,2.0));

    sprintf(result,"%s","_");
    if (magZPLUS > magZMINUS){
       sprintf(result,"%s","z1+iz2");
    }
    if (magZPLUS < magZMINUS){
       sprintf(result,"%s","z1-iz2");
    }
    
    
    return 1;
}

int CreateZlisting(dictionary *scan_file_dict, char *sectionname_z1, char *sectionname_z2, char *plusminus){
    char sectionname_z1startrow[100];
    char sectionname_z2startrow[100];
    char zcombination_filename[200];
    char *z1_filename, *z2_filename;
    char filesuffix[15];
    char tempfilename[200];
    int z1_startrow;
    int z2_startrow;
    int i;
    char *ptr1,*ptr2,*ptrz;
    float maxamp1,maxamp2,maxphase1,maxphase2;
    FILE *fileptrZ1, *fileptrZ2, *zfile;
    char buf[500];
    char buf2[500];
    char *delimiter;
    float az1,el1,amp1,phase1;
    float az2,el2,amp2,phase2;
    float ampZ,phaseZ;
    int narg1, narg2;
    char printmsg[200];
    float intensity1,intensity2;
    float magZPLUS,magZMINUS;
    float z1real,z1imag,z2real,z2imag,newZreal,newZimag;
    char zline[200];
    char *scansection, *scankey;
    
    sprintf(filesuffix,"_%s.txt",plusminus);
    sprintf(sectionname_z1startrow, "%s_startrow", sectionname_z1);
    sprintf(sectionname_z2startrow, "%s_startrow", sectionname_z2);
    z1_filename = iniparser_getstring(scan_file_dict,sectionname_z1,"null");
    z2_filename = iniparser_getstring(scan_file_dict,sectionname_z2,"null");
    z1_startrow = iniparser_getint(scan_file_dict,sectionname_z1startrow,0);
    z2_startrow = iniparser_getint(scan_file_dict,sectionname_z2startrow,0);
    sprintf(tempfilename,"%s",z1_filename);
    sprintf(zcombination_filename,"%s_%s",strtok(tempfilename,"."),filesuffix);
    remove(zcombination_filename);
    fileptrZ1 = fopen(z1_filename,"r");
    fileptrZ2 = fopen(z2_filename,"r");
    zfile = fopen(zcombination_filename,"w");

    //Copy header into new Z-listing file
    for (i=1; i<z1_startrow; i++) {
        ptr1 = fgets(buf,sizeof(buf),fileptrZ1);
        fputs(ptr1,zfile);
    }
    for (i=1; i<z2_startrow; i++) {
        ptr2 = fgets(buf2,sizeof(buf2),fileptrZ2);
    }
    do {
        ptr1 = fgets(buf,sizeof(buf),fileptrZ1);
        ptr2 = fgets(buf2,sizeof(buf2),fileptrZ2);

        if (ptr1 == NULL) break;
            //if(!strcmp(delimiter,",")){      
                 //narg = sscanf(ptr,"%f,%f,%f,%f",&az,&el,&amp,&phase);
            //}
            //if(!strcmp(delimiter,"\t")){  
                 narg1 = sscanf(ptr1,"%f\t%f\t%f\t%f",&az1,&el1,&amp1,&phase1); 
                 narg2 = sscanf(ptr2,"%f\t%f\t%f\t%f",&az2,&el2,&amp2,&phase2); 
            //} 

        intensity1 = pow(10.0,(amp1/20.0));
        z1real = intensity1 * cos(PI * phase1/180.0);
        z1imag = intensity1 * sin(PI * phase1/180.0);
        intensity2 = pow(10.0,(amp2/20.0));
        z2real = intensity2 * cos(PI * phase2/180.0);
        z2imag = intensity2 * sin(PI * phase2/180.0);    
        
        if (!strcmp(plusminus,"z1+iz2")){
           newZreal = 0.5 * (z1real - z2imag);
           newZimag = 0.5 * (z1imag + z2real);                              
        }
        if (!strcmp(plusminus,"z1-iz2")){
           newZreal = 0.5 * (z1real + z2imag);
           newZimag = 0.5 * (z1imag - z2real); 
        }
        
        ampZ = 20.0 * log10(sqrt(pow(newZreal,2.0) + pow(newZimag,2.0)));
        phaseZ = atan2(newZimag, newZreal) * (57.2957795);
        //Write new Az,El,Amp,Phase line to Z-combination listing file
        sprintf(zline,"%f\t%f\t%f\t%f\n",az1,el1,ampZ,phaseZ);
        fputs(zline,zfile);
        
        if (narg1 != 4) {
            sprintf(printmsg,"Error parsing line %d in Z1 file\n",i);
            PRINT_STDOUT(printmsg);
            exit(ERR_LINE_FORMAT_PROBLEM);
        }
        if (narg2 != 4) {
            sprintf(printmsg,"Error parsing line %d in Z2 file\n",i);
            PRINT_STDOUT(printmsg);
            exit(ERR_LINE_FORMAT_PROBLEM);
        }
            
    } while (1);

    fclose(fileptrZ1);
    fclose(fileptrZ2);
    fclose(zfile);
    scansection = strtok(sectionname_z1,":");
    scankey = strtok(NULL,":");
    UpdateDictionary(scan_file_dict, scansection, scankey, zcombination_filename);
    return 1;    
}

