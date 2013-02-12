#include <string.h>
#include <stdio.h> 
#include <math.h> 
#include "iniparser.h"
#include "utilities.h"
#include "plotting_crosspol.h"
#include "outputfilefunctions.h"
extern char *VersionNumber;

int PlotCrosspol(SCANDATA *xpolscan, dictionary *scan_file_dict){ 
 
    char *opencommand, *commandfilename, *gnuplot, *outputdirectory, *outputfilename;
    char fnamebuffer[500];
    char commandfilebuffer[500];
    char commandbuffer[500];
    char contourfilenamebuffer[500];
    char *contourfilename;
    
    gnuplot = iniparser_getstring (scan_file_dict,"settings:gnuplot", "null");
    outputdirectory = iniparser_getstring (scan_file_dict,"settings:outputdirectory", "null");
    
    //Crosspol NF
    sprintf(fnamebuffer,"%snfdata_temp.txt",outputdirectory);

        //NF AMP
        sprintf(commandfilebuffer,"%snfamp_command.txt",outputdirectory);
        WriteCrosspolDataFile(xpolscan, fnamebuffer,"nf");
        WriteCrosspolNF_CommandFile(xpolscan, commandfilebuffer, scan_file_dict, fnamebuffer,"amp");
        sprintf(commandbuffer, "%s %s",gnuplot,commandfilebuffer);
        system(commandbuffer);
        
    
        //NF PHASE
        sprintf(commandfilebuffer,"%snfphase_command.txt",outputdirectory);
        WriteCrosspolNF_CommandFile(xpolscan, commandfilebuffer, scan_file_dict, fnamebuffer,"phase");
        sprintf(commandbuffer, "%s %s",gnuplot,commandfilebuffer);
        system(commandbuffer);

    //Crosspol FF
    sprintf(fnamebuffer,"%sffdata_temp.txt",outputdirectory);
    sprintf(contourfilenamebuffer,"%sffdata_temp_contour.txt",outputdirectory);
    
        //FF AMP
        sprintf(commandfilebuffer,"%sffamp_command.txt",outputdirectory);
        WriteCrosspolDataFile(xpolscan, fnamebuffer,"ff");
        WriteCrosspolFF_CommandFile(xpolscan, commandfilebuffer, scan_file_dict, fnamebuffer,"amp");
        sprintf(commandbuffer, "%s %s",gnuplot,commandfilebuffer);
        system(commandbuffer);
    
    
        //FF PHASE
        sprintf(commandfilebuffer,"%sffphase_command.txt",outputdirectory);
        WriteCrosspolFF_CommandFile(xpolscan, commandfilebuffer, scan_file_dict, fnamebuffer,"phase");
        sprintf(commandbuffer, "%s %s",gnuplot,commandfilebuffer);
        system(commandbuffer);
        
        
    return(0);
}

int WriteCrosspolDataFile(SCANDATA *currentscan, char *outfilename, char *listingtype){
    FILE *fileptr,*fileptrcontour;
    char textline[500];
    long int i;
    int seriescount;
    float serieslength_float;
    long int serieslength_int;
    float amp_norm_value;

    //amplitude is normalized to peak difference between copol,crosspol
    //use crosspolscan->ifatten_difference
    
    
    remove(outfilename);
    fileptr = fopen(outfilename, "w");
    if(fileptr==NULL) {
      printf("Error: can't create file.\r\n");
    }
  
  
  //If Nearfield listing
  amp_norm_value = currentscan->max_nf_amp_db - currentscan->max_dbdifference_nf;

  //Previous version
  /*
  if (!strcmp(listingtype,"nf")){
      serieslength_float = sqrt(currentscan->nf_pts);
      serieslength_int=serieslength_float;
      seriescount=0;
      //for (i=0;i<currentscan->nf_pts;i++){
      for (i=0;i<currentscan->nf_pts;i++){
        //amp_norm_value = currentscan->nf_amp_db[i] + fabs(currentscan->max_nf_amp_db) - currentscan->max_dbdifference_nf;  
        sprintf(textline, "%f\t%f\t%f\t%f\r\n", currentscan->nf_x[currentscan->nf_pts - i - 1],currentscan->nf_y[currentscan->nf_pts - i - 1],
        currentscan->nf_amp_db[i] - (currentscan->max_nf_amp_db + currentscan->max_dbdifference_nf),
        currentscan->nf_phase_deg[i]);
        fputs(textline,fileptr);
        seriescount++;
       // if (seriescount==serieslength_int){
        if (seriescount==currentscan->nf_xpts){                                   
            fputs("\r\n",fileptr);
            seriescount=0;
        }
      }
  }*/
  
  //New version
  if (!strcmp(listingtype,"nf")){
      serieslength_float = sqrt(currentscan->nf_pts);
      serieslength_int=serieslength_float;
      
     
      if (currentscan->nf_ypts > currentscan->nf_xpts){
        serieslength_int=currentscan->nf_ypts;
      }
      if (currentscan->nf_ypts < currentscan->nf_xpts){
        serieslength_int=currentscan->nf_xpts;
      }
      
      seriescount=0;
      //for (i=0;i<currentscan->nf_pts;i++){
      for (i=0;i<currentscan->nf_pts - 1;i++){
        //amp_norm_value = currentscan->nf_amp_db[i] + fabs(currentscan->max_nf_amp_db) - currentscan->max_dbdifference_nf;  
        sprintf(textline, "%f\t%f\t%f\t%f\r\n", currentscan->nf_x[i],currentscan->nf_y[i],
        currentscan->nf_amp_db[i] - (currentscan->max_nf_amp_db + currentscan->max_dbdifference_nf),
        currentscan->nf_phase_deg[i]);
        fputs(textline,fileptr);
        seriescount++;
       // if (seriescount==serieslength_int){
        if (seriescount==serieslength_int){                                   
            fputs("\r\n",fileptr);
            seriescount=0;
        }
      }
  }
  
  
  
  float tempamp;

  //Previous version
  /*
  if (!strcmp(listingtype,"ff")){                         
      serieslength_float = sqrt(currentscan->ff_pts);
      serieslength_int=serieslength_float;
      seriescount=0;
      for (i=0;i<currentscan->ff_pts;i++){
        sprintf(textline, "%f\t%f\t%f\t%f\r\n", currentscan->ff_az[currentscan->ff_pts - i - 1],currentscan->ff_el[currentscan->ff_pts - i - 1],
        currentscan->ff_amp_db[i] - (currentscan->max_ff_amp_db + currentscan->max_dbdifference),
        currentscan->ff_phase_deg[i]);
        fputs(textline,fileptr);
        seriescount++;
        if (seriescount==serieslength_int){
            fputs("\r\n",fileptr);
            seriescount=0;
        }
      }
  } */
  
  
  //Test version
  if (!strcmp(listingtype,"ff")){                         
      serieslength_float = sqrt(currentscan->ff_pts);
      serieslength_int=serieslength_float;
      seriescount=0;
      for (i=0;i<currentscan->ff_pts - 1;i++){
        sprintf(textline, "%f\t%f\t%f\t%f\r\n", currentscan->ff_az[i],currentscan->ff_el[i],
        currentscan->ff_amp_db[i] - (currentscan->max_ff_amp_db + currentscan->max_dbdifference),
        currentscan->ff_phase_deg[i]);
        fputs(textline,fileptr);
        seriescount++;
        if (seriescount==serieslength_int){
            fputs("\r\n",fileptr);
            seriescount=0;
        }
      }
  } 


  fclose(fileptr);  
  return(0);
}


int WriteCrosspolNF_CommandFile(SCANDATA *currentscan, char *outfilename, 
    dictionary *scan_file_dict,char *datafilename,char *datatype){
    FILE *fileptr;
    char textline[500];
    long int i;
    int seriescount;
    float serieslength_float;
    long int serieslength_int;
    char plotfilename[500],*outputdirectory;
    char plotnametemp[500];
    char linebuffer[500];
    char *title;
    char titlebuffer[500];
    char *writeval;

    remove(outfilename);
    outputdirectory = iniparser_getstring (scan_file_dict,"settings:outputdirectory", "null");
    sprintf(plotfilename,"%sband%d_pol%d_%s_%dghz_nf%s_tilt%d_scanset_%d.png",outputdirectory,currentscan->band,
    currentscan->pol,currentscan->type,currentscan->f,datatype,currentscan->tilt,currentscan->scanset);
    sprintf(titlebuffer,"band %d, pol %d %s, %d ghz, tilt %d",currentscan->band,currentscan->pol
    ,currentscan->type,currentscan->f,currentscan->tilt);
    title = titlebuffer;
    fileptr = fopen(outfilename, "w");
    
    
    if(fileptr==NULL) {
       printf("Error: can't create file.\r\n");
    }
    fputs("set terminal png size 500, 500 crop\r\n",fileptr);
    sprintf(linebuffer,"set output '%s'\r\n",plotfilename);
    fputs(linebuffer,fileptr);
    sprintf(linebuffer,"set title '%s'\r\n",title);
    fputs(linebuffer,fileptr);
    
    fputs("set xlabel 'X(m)'\r\n",fileptr);
    fputs("set ylabel 'Y(m)'\r\n",fileptr);
    fputs("set palette model RGB defined (-50 'purple', -40 'blue', -30 'green', -20 'yellow', -10 'orange', 0 'red')\r\n",fileptr);
    
    if (datatype == "amp"){ 
        fputs("set cblabel 'Nearfield Amplitude (dB)'\r\n",fileptr);
        fputs("set view 0,0\r\n",fileptr);
        fputs("set pm3d map\r\n",fileptr);
        fputs("set size square\r\n",fileptr);
        fputs("set cbrange [-50:0]\r\n",fileptr);
        //DB keys and additional info
        		sprintf(linebuffer,"set label 'MeasDate: %s"
        				", BeamEff v%s' at screen 0, screen 0.04\r\n",currentscan->ts,VersionNumber);
        		fputs(linebuffer,fileptr);
        		sprintf(linebuffer,"set label 'ScanSetDetails=%s,"
        				" ScanDetails=%s, FEConfig=%s' "
        				"at screen 0, screen 0.07\r\n",
        				currentscan->scanset_id, currentscan->scan_id, currentscan->fecfg);
        		fputs(linebuffer,fileptr);
        
        fputs("set xtics 0.015\r\n",fileptr);      
        fputs("set ytics 0.015\r\n",fileptr); 
        
        sprintf(linebuffer,"splot '%s' using 1:2:3 title ''\r\n",datafilename);
        fputs(linebuffer,fileptr);
        fputs("\r\n",fileptr);
        UpdateDictionary(scan_file_dict,currentscan->sectionname, "plot_xpol_nfamp", plotfilename);
        
        
    }
    if (datatype == "phase"){
        fputs("set cblabel 'Nearfield Phase (deg)'\r\n",fileptr);
        //fputs("set palette gray\r\n",fileptr);
        fputs("set cbrange [-180:180]\r\n",fileptr);
        //DB keys and additional info
        		sprintf(linebuffer,"set label 'MeasDate: %s"
        				", BeamEff v%s' at screen 0, screen 0.04\r\n",currentscan->ts,VersionNumber);
        		fputs(linebuffer,fileptr);
        		sprintf(linebuffer,"set label 'ScanSetDetails=%s,"
        				" ScanDetails=%s, FEConfig=%s' "
        				"at screen 0, screen 0.07\r\n",
        				currentscan->scanset_id, currentscan->scan_id, currentscan->fecfg);
        		fputs(linebuffer,fileptr);

        fputs("set view 0,0\r\n",fileptr);
        fputs("set pm3d map\r\n",fileptr);
        fputs("set size square\r\n",fileptr);
        sprintf(linebuffer,"splot '%s' using 1:2:4 title ''\r\n",datafilename);
        fputs(linebuffer,fileptr);
        fputs("\r\n",fileptr);
        UpdateDictionary(scan_file_dict,currentscan->sectionname, "plot_xpol_nfphase", plotfilename);
    }
    fclose(fileptr); 
    return 1;


}


int WriteCrosspolFF_CommandFile(SCANDATA *currentscan, char *outfilename, 
   dictionary *scan_file_dict,char *datafilename, char *listingtype){
    FILE *fileptr;
    char textline[500];
    long int i;
    int seriescount;
    float serieslength_float;
    long int serieslength_int;
    char plotfilename[500],*outputdirectory;
    char linebuffer[500];
    char *title;
    char titlebuffer[500];
    
    remove(outfilename);
    outputdirectory = iniparser_getstring (scan_file_dict,"settings:outputdirectory", "null");
    sprintf(plotfilename,"%sband%d_pol%d_%s_%dghz_ff%s_tilt%d_scanset_%d.png",outputdirectory,currentscan->band,
    currentscan->pol,currentscan->type,currentscan->f,listingtype,currentscan->tilt,currentscan->scanset);
    
             
    sprintf(titlebuffer,"band %d, pol %d %s, %d ghz, tilt %d",currentscan->band,currentscan->pol
    ,currentscan->type,currentscan->f,currentscan->tilt);
    title = titlebuffer;
    fileptr = fopen(outfilename, "w");
    
    if(fileptr==NULL) {
       printf("Error: can't create file.\r\n");
    }
    fputs("set terminal png size 500, 500 crop\r\n",fileptr);
    sprintf(linebuffer,"set output '%s'\r\n",plotfilename);
    fputs(linebuffer,fileptr);
    sprintf(linebuffer,"set title '%s'\r\n",title);
    fputs(linebuffer,fileptr);
    
    fputs("set xlabel 'AZ(deg)'\r\n",fileptr);
    fputs("set ylabel 'EL(deg)'\r\n",fileptr);
    fputs("set palette model RGB defined (-50 'purple', -40 'blue', -30 'green', -20 'yellow', -10 'orange', 0 'red')\r\n",fileptr);
    if (listingtype=="amp"){
        fputs("set cblabel 'Farfield Amplitude (dB)'\r\n",fileptr);
        fputs("set view 0,0\r\n",fileptr);
        fputs("set pm3d map\r\n",fileptr);
        fputs("set size square\r\n",fileptr);
        fputs("set cbrange [-50:0]\r\n",fileptr);
        fputs("set parametric\r\n",fileptr);
        fputs("set angles degrees \r\n",fileptr);
        fputs("set urange [0:360]\r\n",fileptr);
        fputs("set xtics 2\r\n",fileptr);           
        fputs("set ytics 2\r\n",fileptr); 
        fputs("set isosamples 13,11\r\n",fileptr);
        //DB keys and additional info
        		sprintf(linebuffer,"set label 'MeasDate: %s"
        				", BeamEff v%s' at screen 0, screen 0.04\r\n",currentscan->ts,VersionNumber);
        		fputs(linebuffer,fileptr);
        		sprintf(linebuffer,"set label 'ScanSetDetails=%s,"
        				" ScanDetails=%s, FEConfig=%s' "
        				"at screen 0, screen 0.07\r\n",
        				currentscan->scanset_id, currentscan->scan_id, currentscan->fecfg);
        		fputs(linebuffer,fileptr);
        
        //sprintf(linebuffer,"splot '%s' using 1:2:3 title ''",datafilename);
        //fputs(linebuffer,fileptr);
        
        sprintf(linebuffer,"splot '%s' using 1:2:3 title '',",datafilename);
        fputs(linebuffer,fileptr);
        sprintf(linebuffer,"%f + 3.58*cos(u),%f + 3.58*sin(u),1 notitle linetype 0 ",
        currentscan->az_nominal,currentscan->el_nominal);
        fputs(linebuffer,fileptr);
        
        fputs("\r\n",fileptr);
        UpdateDictionary(scan_file_dict,currentscan->sectionname, "plot_xpol_ffamp", plotfilename);  
    }
    if (listingtype=="phase"){
        //fputs("set palette gray\r\n",fileptr);
        fputs("set cbrange [-180:180]\r\n",fileptr);
        fputs("set cblabel 'Farfield Phase (deg)'\r\n",fileptr);
        fputs("set view 0,0\r\n",fileptr);
        fputs("set pm3d map\r\n",fileptr);
        fputs("set size square\r\n",fileptr);
        fputs("set parametric\r\n",fileptr);
        fputs("set angles degrees \r\n",fileptr);
        fputs("set urange [0:360]\r\n",fileptr);
        fputs("set xtics 2\r\n",fileptr);           
        fputs("set ytics 2\r\n",fileptr); 
        fputs("set isosamples 13,11\r\n",fileptr);
        //DB keys and additional info
        		sprintf(linebuffer,"set label 'MeasDate: %s"
        				", BeamEff v%s' at screen 0, screen 0.04\r\n",currentscan->ts,VersionNumber);
        		fputs(linebuffer,fileptr);
        		sprintf(linebuffer,"set label 'ScanSetDetails=%s,"
        				" ScanDetails=%s, FEConfig=%s' "
        				"at screen 0, screen 0.07\r\n",
        				currentscan->scanset_id, currentscan->scan_id, currentscan->fecfg);
        		fputs(linebuffer,fileptr);
        
        //sprintf(linebuffer,"splot '%s' using 1:2:4 title ''",datafilename);
        //fputs(linebuffer,fileptr);
        
        sprintf(linebuffer,"splot '%s' using 1:2:4 title '',",datafilename);
        fputs(linebuffer,fileptr);
        sprintf(linebuffer,"%f + 3.58*cos(u),%f + 3.58*sin(u),1 notitle linetype 0 ",
        currentscan->az_nominal,currentscan->el_nominal);
        fputs(linebuffer,fileptr);
        
        fputs("\r\n",fileptr);
        UpdateDictionary(scan_file_dict,currentscan->sectionname, "plot_xpol_ffphase", plotfilename);   
    }
    fclose(fileptr);  
    return(0);
}
