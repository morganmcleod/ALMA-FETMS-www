extern int PlotCopol(SCANDATA *currentscan, dictionary *scan_file_dict) ;
extern int WriteCopolDataFile(SCANDATA *currentscan, char *outfilename, char *listingtype);
extern int WriteCopolNF_CommandFile(SCANDATA *currentscan, char *outfilename, 
				    dictionary *scan_file_dict,char *datafilename,char *datatype);
extern int WriteCopolFF_CommandFile(SCANDATA *currentscan, char *outfilename, 
				    dictionary *scan_file_dict,char *datafilename, char *listingtype);
