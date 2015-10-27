#include "SCANDATA.h"
#include "dictionary.h"

extern int ReadCopolFile(SCANDATA *currentscan, dictionary *scan_file_dict, float subreflector_radius);
///< Load a copol listing file as outputted by NSI2000 software.
///< results are stored in currentscan and in the dictionary.

extern int ReadCrosspolFile(SCANDATA *crosspolscan, SCANDATA *copolscan, dictionary *scan_file_dict);
///< Load the crosspol listing file corresponding to the given previously loaded copol file.
///< results are stored in crosspolscan and in the dictionary.

