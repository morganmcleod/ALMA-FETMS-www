#include <stdio.h>
#include <string.h>
#include "iniparser.h"
#include "utilities.h"
#include "nsi.h"
extern int DEBUGGING;

int GetNSIValues(dictionary *scan_file_dict){
    int num_sections,i;
    char *sectionname;
    char *nf2;
    char *ff2;
    char sectionname_nf2[30];
    char sectionname_ff2[30];
    
    //printf("Reading for NSI values...\n");
    num_sections = iniparser_getnsec(scan_file_dict);
    for (i=0;i<num_sections;i++){
        
        sectionname = iniparser_getsecname(scan_file_dict,i);
        if (!strncmp(sectionname,"scan",4)){
           ReadNSIfile(scan_file_dict,sectionname,"nf");
           ReadNSIfile(scan_file_dict,sectionname,"ff"); 
           
           //Do the same for nf2, ff2, if present
           sprintf(sectionname_nf2,"%s:nf2",sectionname);
           sprintf(sectionname_ff2,"%s:ff2",sectionname);
           
           nf2 = iniparser_getstring(scan_file_dict,sectionname_nf2,"null");
           ff2 = iniparser_getstring(scan_file_dict,sectionname_ff2,"null");
           
           //printf("nf2,ff2= %s\n\n, %s\n",nf2,ff2);
           //getchar();
           
           if ((nf2 != "null") & (ff2 != "null")){
              ReadNSIfile(scan_file_dict,sectionname,"nf2");
              ReadNSIfile(scan_file_dict,sectionname,"ff2"); 
           }
        }
    }
    //printf("NSI files read\n");
    return 1;
}

int ReadNSIfile(dictionary *scan_file_dict, char *sectionname, char *nf_or_ff){
    
    FILE *fileptr;
    long int rowcount;
    char *ptr;
    char filenametemp[400];
    char section_keytemp[30];
    char section_keydatetime[30];
    char buf[500];
    long int listing_startrow;
    char writeval[200];
    char *datetime;
    char *datetimeptr;
    
    sprintf(section_keytemp,"%s:%s",sectionname,nf_or_ff);
    if (DEBUGGING) {
      printf("Search section_keytemp = %s\n",section_keytemp);
    }
    strcpy(filenametemp,iniparser_getstring(scan_file_dict,section_keytemp,"null"));	 
    fileptr = fopen(filenametemp,"r");
    if (fileptr == NULL) {
        printf("nsi: Could not open file = %s\n",filenametemp);
        
        exit(ERR_COULD_NOT_OPEN_FILE);
        return(-1);
    }
    
    rowcount = 0;
    do {
        ptr = fgets(buf,sizeof(buf),fileptr);
        if (ptr == NULL) break;
        rowcount++;
        if(strstr(ptr,"line:")){
            listing_startrow = rowcount + 1;
            strcat(section_keytemp,"_startrow");
            sprintf(writeval,"%ld",listing_startrow);
            iniparser_setstring (scan_file_dict, section_keytemp, writeval); 
        }     
        
        datetimeptr = ptr;
        if(strstr(datetimeptr,"date/time:")){
            sprintf(section_keydatetime,"%s:datetime",sectionname);                    
            datetime = strtok(datetimeptr,":");
            datetime = strtok(NULL,",");
            iniparser_setstring (scan_file_dict, section_keydatetime, datetime);    
        }   
    } while (1);
    
    //printf("%s listingstartrow= %ld\n",nf_or_ff,listing_startrow);
    //getchar();
    
    fclose(fileptr);
    return 1;
}
