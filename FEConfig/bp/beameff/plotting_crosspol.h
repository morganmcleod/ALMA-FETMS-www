extern int PlotCrosspol(SCANDATA *xpolscan, dictionary *scan_file_dict); 
extern int WriteCrosspolDataFile(SCANDATA *currentscan, char *outfilename, char *listingtype);
extern int WriteCrosspolNF_CommandFile(SCANDATA *currentscan, char *outfilename, 
				       dictionary *scan_file_dict,char *datafilename,char *datatype);
extern int WriteCrosspolFF_CommandFile(SCANDATA *currentscan, char *outfilename, 
				       dictionary *scan_file_dict,char *datafilename, char *listingtype);
