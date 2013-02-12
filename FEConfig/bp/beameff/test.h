
void testrep(){
   char x[400];
   char *x2;
   
   strcpy(x,"this/is/a/test");


   ReplaceDelimiter (x,"/","_");
   
   
   printf("replace delim test\n");
   printf("%s\n",x);
   getchar();


}







void getsecnames(dictionary *scan_file_dict){
     char *tempsec;
     char tempseckey[200];
     int i, scancount=0;
     
     for(i=0;i<iniparser_getnsec(scan_file_dict);i++){
         tempsec = iniparser_getsecname(scan_file_dict,i);
         sprintf(tempseckey,"%s:scanset",tempsec);
             if (iniparser_getint (scan_file_dict, tempseckey, -1) != -1){
                     
                     printf("tempsec= %s\n",tempsec);
                     printf("scancount= %d\n",scancount);
                     scancount++;
             }
     }
     
     
     getchar();
     
     
     
}




void test_getu(){
     int x[10];
     int i, unique_count;
      
     x[0] = 3;
     x[1] = 3;
     x[2] = 4;
     x[3] = 5;
     x[4] = 3;
     x[5] = 6;
     x[6] = 7;
     x[7] = 8;
     x[8] = 7;
     x[9] = 9;
     
     unique_count = GetUniqueArrayInt2(x,sizeof(x)/sizeof(int));
     
     for (i=0;i<10;i++){
         printf("int array[%d]= %d\n",i,x[i]);
     }
     
     printf("Unique count = %d\n",unique_count);
     getchar();
}

int GetUniqueArrayInt2(int invals[],int arrsize){
    //Input is an array of integers
    //Duplicate values are deleted from the array
    //Resulting array consists of all uniqe values, followed
    //by "-1" in the remaining array slots.
    //Return value is number of unique elements in array.
     char tempstr1[200];
     char tempstr2[200];
     char tempval[10];
     int i,ucount=1;
     char delims[] = "_";
     char *result = NULL;

     sprintf(tempstr1,"%d_",invals[0]);
     sprintf(tempstr2,"%d_",invals[0]);

     //Copy all values into tempstr1
     //Copy only unique values into tempstr2
     //Insert "_" between each value
     for (i=1;i<arrsize;i++){
         sprintf(tempval,"%d_",invals[i]);
         strcat(tempstr1,tempval);
         
         if (!strstr(tempstr2,tempval)){
                strcat(tempstr2,tempval); 
                ucount++;                     
         }
     }
     
    //Replace input array with array containing
    //only unique values 
    result = strtok( tempstr2, delims );
    for (i=0;i<arrsize;i++){
        if (i<ucount){
            invals[i] = atoi(result);
            result = strtok( NULL, delims );
        }
        if (i>=ucount){
            invals[i] = -1;
        }
    }
    
    
     //for (i=0;i<arrsize;i++){
         //printf("int array[%d]= %d\n",i,invals[i]);
     //}
     //getchar();

     
     return ucount;
}



