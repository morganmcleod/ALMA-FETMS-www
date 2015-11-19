#include <stdio.h>
#include <string.h>
#include <math.h>
#include "constants.h"
#include "iniparser.h"
#include "utilities.h"
#include "z.h"
extern int DEBUGGING;

int GetZ(dictionary *scan_file_dict) {
    int num_sections, i;
    char *sectionname;
    char sectionname_nf[30];
    char sectionname_ff[30];
    char sectionname_nf2[30];
    char sectionname_ff2[30];
    char ZplusOrZminus[6];
    char *found;
    
    // Loop on number of sections in the input dictionary:
    num_sections = iniparser_getnsec(scan_file_dict);
    for (i=0;i<num_sections;i++){
        
        // If the section name starts with "scan" then continue...
        sectionname = iniparser_getsecname(scan_file_dict,i);
        if (!strncmp(sectionname,"scan",4)) {

           // Make section:key names for reading the input dictionary:
           sprintf(sectionname_nf,"%s:nf",sectionname);
           sprintf(sectionname_ff,"%s:ff",sectionname);
           sprintf(sectionname_nf2,"%s:nf2",sectionname);
           sprintf(sectionname_ff2,"%s:ff2",sectionname);

           // Check if beam2 listings are present
           found = iniparser_getstring(scan_file_dict, sectionname_nf2, "null");
           if (strcmp(found, "null") != 0) {
               // Call helper which combines the nf and nf2 files:
               GetZplusOrZminus(scan_file_dict, sectionname, "nf", ZplusOrZminus);
               // Write out a new listing file with the combined data:
               CreateZlisting(scan_file_dict, sectionname_nf, sectionname_nf2, ZplusOrZminus);
           }
           found = iniparser_getstring(scan_file_dict, sectionname_ff2, "null");
           if (strcmp(found, "null") != 0) {
               // Call helper which combines the ff and ff2 files:
               GetZplusOrZminus(scan_file_dict, sectionname, "ff", ZplusOrZminus);
               // Write out a new listing file with the combined data:
               CreateZlisting(scan_file_dict, sectionname_ff, sectionname_ff2, ZplusOrZminus);
           }
        }
    }     
    return 1;
}

int GetZplusOrZminus(dictionary *scan_file_dict, char *sectionname, char *nf_or_ff, char result[]) {
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
    char scanStr[100];
    char *delimiter;
    float az,el,amp,phase;
    int narg;
    char printmsg[200];
    float z1real,z1imag,z2real,z2imag;
    float intensity1,intensity2;
    float magZPLUS,magZMINUS;
    int maxamp_index = 0;
    int count = 0;
    
    // Make keys for reading the input files and start rows from the input dictionary:
    sprintf(sectionname_z1,"%s:%s",sectionname,nf_or_ff);
    sprintf(sectionname_z2,"%s:%s2",sectionname,nf_or_ff);
    sprintf(sectionname_z1startrow,"%s:%s_startrow",sectionname,nf_or_ff);
    sprintf(sectionname_z2startrow,"%s:%s2_startrow",sectionname,nf_or_ff);

    // read the filenames and start rows from the dictionary:
    z1_filename = iniparser_getstring(scan_file_dict, sectionname_z1, "null");
    z2_filename = iniparser_getstring(scan_file_dict, sectionname_z2, "null");
    z1_startrow = iniparser_getint(scan_file_dict, sectionname_z1startrow, 0);
    z2_startrow = iniparser_getint(scan_file_dict, sectionname_z2startrow, 0);
    
    // If both filenames are not provided, return early:
    if (!strcmp(z1_filename, "null") || !strcmp(z2_filename, "null"))
        return 1;

    // Make a scanf string based on delimiter:
    delimiter = iniparser_getstring(scan_file_dict, "settings:delimiter", "\t");
    sprintf(scanStr, "%s%s%s%s%s%s%s%s", "%f", delimiter, "%f", delimiter, "%f", delimiter, "%f");

    // Init vars to accumulate max amplitude and phase:
    maxamp1 = -900;
    maxamp2 = -900;
    maxphase1 = -900;
    maxphase2 = -900;

    // Open z1 file, skip the header
    fileptrZ1 = fopen(z1_filename, "r");
    for (i = 1; i < z1_startrow; i++) {
        ptr = fgets(buf, sizeof(buf), fileptrZ1);
    }
    // Loop to load z1 file:
    do {
        count++;
        ptr = fgets(buf, sizeof(buf), fileptrZ1);

        // break at EOF:
        if (ptr == NULL)
            break;

        // Scan the row into our variables:
        narg = sscanf(ptr, scanStr, &az, &el, &amp, &phase);

        // Check for bad data:
        if (narg != 4) {
            sprintf(printmsg, "Error parsing line %d\n", i);
            PRINT_STDOUT(printmsg);
            exit(ERR_LINE_FORMAT_PROBLEM);
        }

        // Accumulate max amplitude, phase at max amplitude:
        if (amp > maxamp1){
            maxamp1 = amp;
            maxphase1 = phase;
            maxamp_index = count;
        }
            
    } while (1);
    fclose(fileptrZ1);

    // Open z2 file, skip the header:
    fileptrZ2 = fopen(z2_filename, "r");
    count = 0;
    for (i = 1; i < z2_startrow; i++) {
        ptr = fgets(buf, sizeof(buf), fileptrZ2);
    }
    // Loop to load z2 file:
    do {
        count++;
        ptr = fgets(buf,sizeof(buf),fileptrZ2);

        // break at EOF:
        if (ptr == NULL)
            break;

        // Scan the row into our variables:
        narg = sscanf(ptr, scanStr, &az, &el, &amp, &phase);

        // Check for bad data:
        if (narg != 4) {
            sprintf(printmsg,"Error parsing line %d\n",i);
            PRINT_STDOUT(printmsg);
            exit(ERR_LINE_FORMAT_PROBLEM);
        }
            
        // Store amp and phase at the same point as in z1:
        if (count==maxamp_index){
            maxamp2 = amp;
            maxphase2 = phase;
        }
    } while (1);
    fclose(fileptrZ2);

    // Calculate whether to add or subtract z2 based on the amps and phases found:
    intensity1 = pow(10.0, (maxamp1 / 20.0));
    z1real = intensity1 * cos(PI * maxphase1 / 180.0);
    z1imag = intensity1 * sin(PI * maxphase1 / 180.0);
    intensity2 = pow(10.0, (maxamp2 / 20.0));
    z2real = intensity2 * cos(PI * maxphase2 / 180.0);
    z2imag = intensity2 * sin(PI * maxphase2 / 180.0);
    magZPLUS = sqrt(pow(z1real - z2imag, 2.0) + pow(z1imag + z1real, 2.0));
    magZMINUS = sqrt(pow(z1real + z2imag, 2.0) + pow(z1imag - z2real, 2.0));

    sprintf(result,"%s","_");
    if (magZPLUS > magZMINUS) {
       sprintf(result, "%s", "z1+iz2");
    } else if (magZPLUS < magZMINUS) {
       sprintf(result, "%s", "z1-iz2");
    }
    return 1;
}

int CreateZlisting(dictionary *scan_file_dict, char *sectionname_z1, char *sectionname_z2, char *ZplusOrZminus){
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
    char scanStr[100];
    char *scansection, *scankey;

    // Make keys for reading the input files and start rows from the input dictionary:
    sprintf(sectionname_z1startrow, "%s_startrow", sectionname_z1);
    sprintf(sectionname_z2startrow, "%s_startrow", sectionname_z2);

    // read the filenames and start rows from the dictionary:
    z1_filename = iniparser_getstring(scan_file_dict, sectionname_z1, "null");
    z2_filename = iniparser_getstring(scan_file_dict, sectionname_z2, "null");
    z1_startrow = iniparser_getint(scan_file_dict, sectionname_z1startrow, 0);
    z2_startrow = iniparser_getint(scan_file_dict, sectionname_z2startrow, 0);

    // make the output file name like the z1 input with the ZplusOrZminus string appended:
    strcpy(tempfilename, z1_filename);
    sprintf(filesuffix, "_%s.txt", ZplusOrZminus);
    sprintf(zcombination_filename, "%s_%s", strtok(tempfilename, "."), filesuffix);

    // delete the output file if it exists:
    remove(zcombination_filename);

    // open input files and output file:
    fileptrZ1 = fopen(z1_filename,"r");
    fileptrZ2 = fopen(z2_filename,"r");
    zfile = fopen(zcombination_filename,"w");

    // Make a scanf string based on delimiter:
    delimiter = iniparser_getstring(scan_file_dict, "settings:delimiter", "\t");
    sprintf(scanStr, "%s%s%s%s%s%s%s%s", "%f", delimiter, "%f", delimiter, "%f", delimiter, "%f");

    // Copy z1 header into new Z-listing file:
    for (i = 1; i < z1_startrow; i++) {
        ptr1 = fgets(buf,sizeof(buf),fileptrZ1);
        fputs(ptr1, zfile);
    }
    // skip Z2 header:
    for (i = 1; i < z2_startrow; i++) {
        ptr2 = fgets(buf2,sizeof(buf2),fileptrZ2);
    }
    // loop to read files:
    do {
        ptr1 = fgets(buf,sizeof(buf),fileptrZ1);
        ptr2 = fgets(buf2,sizeof(buf2),fileptrZ2);

        // break on z1 file EOF:
        if (ptr1 == NULL)
            break;

        // Scan the rows from both input files into our variables:
        narg1 = sscanf(ptr1, scanStr, &az1, &el1, &amp1, &phase1);
        narg2 = sscanf(ptr2, scanStr, &az2, &el2, &amp2, &phase2);

        // Check for bad data:
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

        // Compute new output values from values read:
        intensity1 = pow(10.0 , (amp1 / 20.0));
        z1real = intensity1 * cos(PI * phase1 / 180.0);
        z1imag = intensity1 * sin(PI * phase1 / 180.0);
        intensity2 = pow(10.0 , (amp2 / 20.0));
        z2real = intensity2 * cos(PI * phase2 / 180.0);
        z2imag = intensity2 * sin(PI * phase2 / 180.0);
        
        if (!strcmp(ZplusOrZminus,"z1+iz2")){
           newZreal = 0.5 * (z1real - z2imag);
           newZimag = 0.5 * (z1imag + z2real);                              
        }
        if (!strcmp(ZplusOrZminus,"z1-iz2")){
           newZreal = 0.5 * (z1real + z2imag);
           newZimag = 0.5 * (z1imag - z2real); 
        }
        ampZ = 20.0 * log10(sqrt(pow(newZreal,2.0) + pow(newZimag,2.0)));
        phaseZ = atan2(newZimag, newZreal) * (57.2957795);  // radians/deg
        
        // Write new Az,El,Amp,Phase line to Z-combination listing file
        sprintf(zline, scanStr, az1, el1, ampZ, phaseZ);
        fputs(zline, zfile);
        fputs("\n", zfile);
    } while (1);

    // close files:
    fclose(fileptrZ1);
    fclose(fileptrZ2);
    fclose(zfile);

    // Update the input dictionary with the new filename to use:
    scansection = strtok(sectionname_z1, ":");
    scankey = strtok(NULL, ":");
    UpdateDictionary(scan_file_dict, scansection, scankey, zcombination_filename);
    return 1;    
}

