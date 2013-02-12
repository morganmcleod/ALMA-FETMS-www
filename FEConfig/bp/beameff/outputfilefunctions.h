extern int Testfun(dictionary *scan_file_dict);
extern int WriteCopolData(dictionary *scan_file_dict, SCANDATA *currentscan, char *outputfilename);
extern int WriteCrosspolData(dictionary *scan_file_dict, SCANDATA *currentscan, char *outputfilename);
extern int GetOutputFilename(dictionary *scan_file_dict,char outname[400]);
extern int SaveOutputFile(dictionary *scan_file_dict, char *outputfilename);
extern int UpdateDictionary(dictionary *scan_file_dict, char *sectionname, char *keyname, char *writeval);


