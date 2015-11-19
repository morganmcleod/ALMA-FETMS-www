#include <stdio.h>
#include <string.h>
#include "iniparser.h"
#include "utilities.h"
#include "nsi.h"
extern int DEBUGGING;

int GetNSIValues(dictionary *scan_file_dict) {
    // GetNSIValues finds the first row of data and the datetime string from NSI formatted text files.
    // It reads up to four files specificed by nf, ff, nf2, and ff2 in the input dictionary.
    // If found, the resulting values are stored in the dictionary.

    int num_sections, i;
    char *sectionname;
    char *nf2;
    char *ff2;
    char sectionname_nf2[30];
    char sectionname_ff2[30];
    
    // Loop on number of sections in the input dictionary:
    num_sections = iniparser_getnsec(scan_file_dict);
    for (i = 0; i < num_sections ;i++) {
        
        // If the section name starts with "scan" then load it:
        sectionname = iniparser_getsecname(scan_file_dict, i);
        if (!strncmp(sectionname, "scan", 4)) {

            // Read the nf and ff files from the scan:
            ReadNSIfile(scan_file_dict, sectionname, "nf");
            ReadNSIfile(scan_file_dict, sectionname, "ff");
           
            // Check for optional nf2, and ff2 keys:
            sprintf(sectionname_nf2, "%s:nf2", sectionname);
            sprintf(sectionname_ff2, "%s:ff2", sectionname);
            nf2 = iniparser_getstring(scan_file_dict, sectionname_nf2, "null");
            ff2 = iniparser_getstring(scan_file_dict, sectionname_ff2, "");
           
            // If both are found, read in those files:
            if ((strcmp(nf2, "null") != 0) && (strcmp(ff2, "null") != 0)) {
                ReadNSIfile(scan_file_dict,sectionname,"nf2");
                ReadNSIfile(scan_file_dict,sectionname,"ff2");
           }
        }
    }
    return 1;
}

int ReadNSIfile(dictionary *scan_file_dict, char *sectionname, char *nf_or_ff){
    // Read a single nf or ff listing file as given by the keys in the given sectionname.
    
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
    
    // Make the key name for the listing filename:
    sprintf(section_keytemp, "%s:%s", sectionname, nf_or_ff);
    if (DEBUGGING) {
        printf("ReadNSIfile: looking for section_keytemp = %s\n", section_keytemp);
    }
    // Load the filename:
    strcpy(filenametemp, iniparser_getstring(scan_file_dict, section_keytemp, "null"));

    // Attempt to open the file for read:
    fileptr = fopen(filenametemp, "r");
    if (fileptr == NULL) {
        printf("nsi: Could not open file = %s\n",filenametemp);
        // Fatal error if we can't open the specified file.
        exit(ERR_COULD_NOT_OPEN_FILE);
        // TODO:  Unreachable code?
        return(-1);
    }
    
    // File is open.  Start reading rows:
    rowcount = 0;
    do {
        ptr = fgets(buf, sizeof(buf), fileptr);
        if (ptr == NULL)
            break;  // Quit loop at EOF.

        rowcount++;
        // Search for marker indicating data start in NSI text format listings:
        if (strstr(ptr, "line:")) {
            listing_startrow = rowcount + 1;
            // save the row count to the input dictionary as like "ff_startrow":
            strcat(section_keytemp, "_startrow");
            sprintf(writeval, "%ld", listing_startrow);
            iniparser_setstring(scan_file_dict, section_keytemp, writeval);
        }

        // Search for marker indicating data and time in NSI text format listings:
        if (strstr(ptr, "date/time:")) {
            // Parse the datetime from the listing file:
            datetimeptr = ptr;
            datetime = strtok(datetimeptr, ":");
            datetime = strtok(NULL, ",");
            // Save to the datetime key in the input dictionary:
            sprintf(section_keydatetime, "%s:datetime", sectionname);
            iniparser_setstring(scan_file_dict, section_keydatetime, datetime);
        }
        // Loop will break at EOF.
    } while (1);
    
    if (DEBUGGING) {
        printf("%s listingstartrow=%ld datetime=%s\n", nf_or_ff, listing_startrow, datetime);
        //getchar();
    }
    
    fclose(fileptr);
    return 1;
}
