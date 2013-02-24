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
  int *scansetnumbers;
  int scanset_array[200];
  char printmsg[200];

  sprintf(printmsg,"********************************************<br>\n",VersionNumber);
  PRINT_STDOUT(printmsg);
  sprintf(printmsg,"Beam Efficiency Calculator Version  %s<br>\n",VersionNumber);
  PRINT_STDOUT(printmsg);
  sprintf(printmsg,"********************************************<br>\n<br>\n",VersionNumber);
  PRINT_STDOUT(printmsg);
    
  //Temporary input file name can be put here for debugging:
  inputfile = "";
  if (argc > 1){  
     inputfile = argv[1];
  }

  if (!strlen(inputfile)) {
	  PRINT_STDOUT("Must provide input file as command line parameter.  Stopping.\n");
	  return -1;
  }

  //Create dictionary from the input text file
  scan_file_dict = iniparser_load(inputfile);
  num_scansets=GetScanSetNumberArray(scan_file_dict,&scanset_array,200);
  sprintf(printmsg,"Input File: %s<br>\n",inputfile);

  PRINT_STDOUT(printmsg);
  sprintf(printmsg,"Number of scans: %d<br>\n",GetNumberOfScans(scan_file_dict));
  PRINT_STDOUT(printmsg);
  
  GetNSIValues(scan_file_dict);
  GetZ(scan_file_dict);
  GetOutputFilename(scan_file_dict,outfilename);
  sprintf(printmsg,"Number of scan sets= %d<br>\n",num_scansets);
  PRINT_STDOUT(printmsg);

  for (i=0;i<num_scansets;i++){
    //Call efficiency module for each scan set. A scan set
    //consists of four scans at a certain band, frequency and
    //tilt angle.
    sprintf(printmsg,"Getting efficiencies and plots, scanset %d of %d...<br>\n",i+1,num_scansets);
    PRINT_STDOUT(printmsg);
    GetEfficiencies(scan_file_dict,scanset_array[i],outfilename);       
  }
  PRINT_STDOUT("Generating pointing angle plots...<br>\n");
  PlotCircles(scan_file_dict);
  RemoveKeys(scan_file_dict);
  sprintf(printmsg,"Saving output to file: %s<br>\n",outfilename);
  PRINT_STDOUT(printmsg);
  SaveOutputFile(scan_file_dict, outfilename);
  iniparser_freedict(scan_file_dict);
  PRINT_STDOUT("finished.<br>\n");
  return 0;
}



    
    


