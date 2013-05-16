#include <string.h>
#include <stdio.h> 
#include <math.h> 
#include "iniparser.h"
#include "utilities.h"
#include "plotting_copol.h"
#include "outputfilefunctions.h"

extern char *VersionNumber;

int PlotCopol(SCANDATA *currentscan, dictionary *scan_file_dict){ 
 
    char *opencommand, *commandfilename, *gnuplot, *outputdirectory, *outputfilename;
    char fnamebuffer[500];
    char commandfilebuffer[500];
    char commandbuffer[500];
    char contourfilenamebuffer[500];
    char *contourfilename;
    
    gnuplot = iniparser_getstring (scan_file_dict,"settings:gnuplot", "null");
    outputdirectory = iniparser_getstring (scan_file_dict,"settings:outputdirectory", "null");
    
    //Copol NF
    sprintf(fnamebuffer,"%snfdata_temp.txt",outputdirectory);
    if (remove(fnamebuffer) == 0){
    }

    //NF AMP
    sprintf(commandfilebuffer,"%snfamp_command.txt",outputdirectory);
    WriteCopolDataFile(currentscan, fnamebuffer,"nf");
    WriteCopolNF_CommandFile(currentscan, commandfilebuffer, scan_file_dict, fnamebuffer,"amp");
    sprintf(commandbuffer, "%s %s",gnuplot,commandfilebuffer);
    system(commandbuffer);

    //NF PHASE
    sprintf(commandfilebuffer,"%snfphase_command.txt",outputdirectory);
    WriteCopolNF_CommandFile(currentscan, commandfilebuffer, scan_file_dict, fnamebuffer,"phase");
    sprintf(commandbuffer, "%s %s",gnuplot,commandfilebuffer);
    system(commandbuffer);

    //Copol FF
    sprintf(fnamebuffer,"%sffdata_temp.txt",outputdirectory);
    sprintf(contourfilenamebuffer,"%sffdata_temp_contour.txt",outputdirectory);
    
    //FF AMP
    sprintf(commandfilebuffer,"%sffamp_command.txt",outputdirectory);
    WriteCopolDataFile(currentscan, fnamebuffer,"ff");
    WriteCopolFF_CommandFile(currentscan, commandfilebuffer, scan_file_dict, fnamebuffer,"amp");
    sprintf(commandbuffer, "%s %s",gnuplot,commandfilebuffer);
    system(commandbuffer);

    //FF PHASE
    sprintf(commandfilebuffer,"%sffphase_command.txt",outputdirectory);
    WriteCopolFF_CommandFile(currentscan, commandfilebuffer, scan_file_dict, fnamebuffer,"phase");
    sprintf(commandbuffer, "%s %s",gnuplot,commandfilebuffer);
    system(commandbuffer);
    return(0);
}

int WriteCopolDataFile(SCANDATA *currentscan, char *outfilename, char *listingtype){
    FILE *fileptr,*fileptrcontour;
    char textline[500];
    long int i;
    int seriescount;
    float serieslength_float;
    long int serieslength_int;
    float tempradius;
    
    remove(outfilename);
    fileptr = fopen(outfilename, "w");
    if(fileptr==NULL) {
      printf("Error: can't create file.\r\n");
    }
  
  
  //If Nearfield listing
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
      for (i=0;i<currentscan->nf_pts - 1;i++){
        //sprintf(textline, "%f\t%f\t%f\t%f\r\n", currentscan->nf_x[i],currentscan->nf_y[i],
       // currentscan->nf_amp_db[i]+fabs(currentscan->max_nf_amp_db),currentscan->nf_phase_deg[i]);
        
        sprintf(textline, "%f\t%f\t%f\t%f\r\n", currentscan->nf_x[i],currentscan->nf_y[i],
        currentscan->nf_amp_db[i]+fabs(currentscan->max_nf_amp_db),currentscan->nf_phase_deg[i]);
        
        fputs(textline,fileptr);
        seriescount++;
        if (seriescount==serieslength_int){
            fputs("\r\n",fileptr);
            seriescount=0;
        }
      }
  }  
  
  
  
  float tempamp;
  
  if (!strcmp(listingtype,"ff")){                         
      serieslength_float = sqrt(currentscan->ff_pts);
      serieslength_int=serieslength_float;
      seriescount=0;
      for (i=0;i<currentscan->ff_pts - 1;i++){
        sprintf(textline, "%f\t%f\t%f\t%f\r\n", currentscan->ff_az[i],currentscan->ff_el[i],
        currentscan->ff_amp_db[i]+fabs(currentscan->max_ff_amp_db),currentscan->ff_phase_deg[i]);

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


int WriteCopolNF_CommandFile(SCANDATA *currentscan, char *outfilename, 
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
        fputs(linebuffer,fileptr);
        fputs("set view 0,0\r\n",fileptr);
        fputs("set pm3d map\r\n",fileptr);
        fputs("set size square\r\n",fileptr);
        fputs("set cbrange [-50:0]\r\n",fileptr);

        //DB keys and additional info
		sprintf(linebuffer,"set label 'MeasDate: %s"
				", BeamEff v%s' at screen 0.01, 0.04\r\n",currentscan->ts,VersionNumber);
		fputs(linebuffer,fileptr);
		sprintf(linebuffer,"set label 'ScanSetDetails=%s,"
				" ScanDetails=%s, FEConfig=%s' "
				"at screen 0.01, 0.07\r\n",
				currentscan->scanset_id, currentscan->scan_id, currentscan->fecfg);
		fputs(linebuffer,fileptr);


        sprintf(linebuffer,"splot '%s' using 1:2:3 title ''\r\n",datafilename,title);
        fputs(linebuffer,fileptr);
        sprintf(linebuffer,"set title '%s'\r\n",title);
        fputs(linebuffer,fileptr);
        fputs("replot\r\n",fileptr);
        fputs("\r\n",fileptr);
        UpdateDictionary(scan_file_dict,currentscan->sectionname, "plot_copol_nfamp", plotfilename);
    }
    if (datatype == "phase"){
        //fputs("set palette gray\r\n",fileptr);
        fputs("set cbrange [-180:180]\r\n",fileptr);
        fputs("set cblabel 'Nearfield Phase (deg)'\r\n",fileptr);
        fputs("set view 0,0\r\n",fileptr);
        fputs("set pm3d map\r\n",fileptr);
        fputs("set size square\r\n",fileptr);

        //DB keys and additional info
		sprintf(linebuffer,"set label 'MeasDate: %s"
				", BeamEff v%s' at screen 0.01, 0.04\r\n",currentscan->ts,VersionNumber);
		fputs(linebuffer,fileptr);
		sprintf(linebuffer,"set label 'ScanSetDetails=%s,"
				" ScanDetails=%s, FEConfig=%s' "
				"at screen 0.01, 0.07\r\n",
				currentscan->scanset_id, currentscan->scan_id, currentscan->fecfg);
		fputs(linebuffer,fileptr);


        sprintf(linebuffer,"splot '%s' using 1:2:4 title ''\r\n",datafilename);
        fputs(linebuffer,fileptr);
        
        /*
        sprintf(linebuffer,"%f + 0.2*cos(u),%f + 0.2*sin(u), 1 notitle lt -1 ",
        currentscan->nf_xcenter + currentscan->delta_x,currentscan->nf_ycenter + currentscan->delta_y);
        fputs(linebuffer,fileptr);
        */
        


        fputs("\r\n",fileptr);
        UpdateDictionary(scan_file_dict,currentscan->sectionname, "plot_copol_nfphase", plotfilename); 
    }
    fclose(fileptr); 
    return 1;
}


int WriteCopolFF_CommandFile(SCANDATA *currentscan, char *outfilename, 
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
        fputs("set cbrange [-50:0]\r\n",fileptr);
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
				", BeamEff v%s' at screen 0.01, 0.04\r\n",currentscan->ts,VersionNumber);
		fputs(linebuffer,fileptr);
		sprintf(linebuffer,"set label 'ScanSetDetails=%s,"
				" ScanDetails=%s, FEConfig=%s' "
				"at screen 0.01, 0.07\r\n",
				currentscan->scanset_id, currentscan->scan_id, currentscan->fecfg);
		fputs(linebuffer,fileptr);

        //Remove this part when uncommenting the part that follows
        //sprintf(linebuffer,"splot '%s' using 1:2:3 title ''",datafilename);
        //fputs(linebuffer,fileptr);
        
        sprintf(linebuffer,"splot '%s' using 1:2:3 title '',",datafilename);
        fputs(linebuffer,fileptr);
        sprintf(linebuffer,"%f + 3.58*cos(u),%f + 3.58*sin(u),1 notitle linetype 0 ",
        currentscan->az_nominal,currentscan->el_nominal);
        fputs(linebuffer,fileptr);
        
        fputs("\r\n",fileptr);
        UpdateDictionary(scan_file_dict,currentscan->sectionname, "plot_copol_ffamp", plotfilename);
        
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
				", BeamEff v%s' at screen 0.01, 0.04\r\n",currentscan->ts,VersionNumber);
		fputs(linebuffer,fileptr);
		sprintf(linebuffer,"set label 'ScanSetDetails=%s,"
				" ScanDetails=%s, FEConfig=%s' "
				"at screen 0.01, 0.07\r\n",
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
        UpdateDictionary(scan_file_dict,currentscan->sectionname, "plot_copol_ffphase", plotfilename);
        
        
        /*
        sprintf(linebuffer,"splot '%s' using 1:2:4 title '',",datafilename);
        fputs(linebuffer,fileptr);
        sprintf(linebuffer,"%f + 3.58*cos(u),%f + 3.58*sin(u), 1 notitle linetype 0,",
        currentscan->az_nominal,currentscan->el_nominal);
        fputs(linebuffer,fileptr);
        sprintf(linebuffer,"%f + 0.2*cos(u),%f + 0.2*sin(u), 1 notitle lt -1 ",
        currentscan->az_nominal + currentscan->delta_x,currentscan->el_nominal + currentscan->delta_y);
        fputs(linebuffer,fileptr);
        fputs("\r\n",fileptr);
        UpdateDictionary(scan_file_dict,currentscan->sectionname, "plot_copol_ffphase", plotfilename);
        */
        
    }
    fclose(fileptr);  
    return(0);
}
