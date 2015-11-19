#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <ctype.h>
#include <math.h>
#include <unistd.h>
#include "nr.h"
#include "iniparser.h"
#include "constants.h"
#include "utilities.h"
#include "fitphase.h"
#include "fitamplitude.h"
#ifndef LINUX
  #include "bzero.c"
#endif
#include "test.h"
#include "pointingangles.h"
#include "getarrays.h"
#include "efficiency.h"
#include "outputfilefunctions.h"
#include "plotting_copol.h"
#include "plotting_crosspol.h"
#include "plotcircles.h"
#include "nsi.h"

int DEBUGGING = 0;

int main(int argc, char *argv[])
{
  int i,num_scans,num_scansets;
  char *counter;
  char outfilename[400];
  char sectionname[20];
  char *delimiter;
  dictionary *scan_file_dict,*scan_file_dict2;
  char *inputfile;
  char *outputfilename;
  char *outputdirectory;
  int *scansetnumbers;
  int scanset_array[200];
  char printmsg[200];

  printf("********************************************<br>\n");
  printf("Beam Efficiency Calculator Version  %s<br>\n",VersionNumber);
  printf("********************************************<br>\n<br>\n");

  //Temporary input file name can be put here for debugging:
  inputfile = "";
  if (argc > 1){  
     inputfile = argv[1];
  }

  if (!strlen(inputfile)) {
	  PRINT_STDOUT("Must provide input file as command line parameter.  Stopping.\n");
	  return -1;
  }

  // Create dictionary from the input text file
  scan_file_dict = iniparser_load(inputfile);

  // Each [scan_n] section specifies which scanset it is part of.
  // Scansets are numbered 1,2,3...   Up to 200 can be loaded:
  num_scansets = GetScanSetNumberArray(scan_file_dict, &scanset_array, 200);

  // Get the specified output directory
  outputdirectory = iniparser_getstring(scan_file_dict, "settings:outputdirectory", "null");
  strcpy(stdOutDirectory, outputdirectory);

  sprintf(printmsg, "Input File: %s<br>\n", inputfile);
  PRINT_STDOUT(printmsg);

  sprintf(printmsg, "Number of scans: %d<br>\n", GetNumberOfScans(scan_file_dict));
  PRINT_STDOUT(printmsg);

  sprintf(printmsg, "Number of scansets: %d<br>\n", num_scansets);
  PRINT_STDOUT(printmsg);
  
  // Finds the first row of data and the datetime string from NSI formatted text files:
  GetNSIValues(scan_file_dict);

  // If there are two z-distances per scan, combine them to reduce the effect of reflections:
  GetZ(scan_file_dict);

  GetOutputFilename(scan_file_dict, outfilename);

  sprintf(printmsg, "Number of scan sets= %d<br>\n", num_scansets);
  PRINT_STDOUT(printmsg);

  for (i=0;i<num_scansets;i++){
    //Call efficiency module for each scan set. A scan set
    //consists of four scans at a certain band, frequency and
    //tilt angle.
    sprintf(printmsg, "Getting efficiencies and plots, scanset %d of %d...<br>\n", i+1, num_scansets);
    PRINT_STDOUT(printmsg);
    GetEfficiencies(scan_file_dict, scanset_array[i], outfilename);
  }
  PRINT_STDOUT("Generating pointing angle plots...<br>\n");
  PlotCircles(scan_file_dict);
  RemoveKeys(scan_file_dict);
  sprintf(printmsg, "Saving output to file: %s<br>\n", outfilename);
  PRINT_STDOUT(printmsg);
  SaveOutputFile(scan_file_dict, outfilename);
  iniparser_freedict(scan_file_dict);
  PRINT_STDOUT("finished.<br>\n");
  return 0;
}

    
    


