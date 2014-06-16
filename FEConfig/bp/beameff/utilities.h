#include "SCANDATA.h"

extern char stdOutDirectory[];

#define WARN(args) sprintf(warning,args);warn(); 
#define WARNING_LENGTH 200
extern void warn(void);
extern char warning[WARNING_LENGTH];

#define PRINT_STDOUT(args) sprintf(printmessage,args);print_stdout(); 
#define MESSAGE_LENGTH 200
extern void print_stdout(void);
extern char printmessage[MESSAGE_LENGTH];


#define MAX_TOKENS 50 /* max number of fields on the line containing the frequency in the CSV file */

#ifdef LINUX
/* the following 2 functions are from Kernighan and Ritchie */
extern void kreverse(char s[]);
char *itoa(int n, char s[], int unusedLength);
#endif

enum {
  ERR_NOT_ENOUGH_ROWS=1,
  ERR_LINE_FORMAT_PROBLEM,
  ERR_COULD_NOT_OPEN_FILE,
  ERR_BAD_ARGUMENT,
  ERR_OUT_OF_MEMORY
};

extern void GetScanData(dictionary *scan_file_dict, char *sectionname, SCANDATA *);
extern int GetNumberOfScans(dictionary *scan_file_dict);
extern int GetNumberOfBands(dictionary *scan_file_dict);
extern int RemoveKeys(dictionary *scan_file_dict);
extern int GetNumberOfScanSetsForBand(dictionary *scan_file_dict, int band);
extern int GetNumberOfScanSets(dictionary *scan_file_dict);
extern int tokenizeDelimiter(char *input, char *tokenArray[MAX_TOKENS], char *delimiter);
extern int PickNominalAngles(int almaBand, float *xtarget, float *ytarget);
extern int GetUniqueArrayInt(int invals[],int arrsize);
extern int ReplaceDelimiter(char input[400], const char *olddelim, const char *newdelim);

