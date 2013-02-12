#include <stdio.h>
#include <math.h>
#include <string.h>
#include "iniparser.h"
#include "utilities.h"
#include "fitamplitude.h"
#include "nr.h"

extern int DEBUGGING;
float function_amp(float p[]);
void dfunction_amp(float p[], float df[]);

SCANDATA *ampfitscan;
const int nterms_amplitude = 6;

int FitAmplitude(SCANDATA *currentscan) { 
    float ftol = 1.0e-5;
    int iter_amp; 
    float fret_amp, amp_fit_term;
    float p[nterms_amplitude+1];
    void (*dfunctionamp)(float p[], float df[]);
    float (*functionamp)(float p[]);
    
    
    ftol = pow(10, -1.0*currentscan->band);


    if (DEBUGGING) {
      fprintf(stderr,"Entered FitAmplitude\n");
    }
    ampfitscan = currentscan;
    functionamp = &function_amp;
    dfunctionamp = &dfunction_amp;

    //Initial guess values may be substituted later
    //p1=amp
    //p2=width (deg)
    //p3=u_offset (deg)
    //p4=v_offset (deg)
    //p5=D_0-90
    //p6=D_45-135
    p[1] =  1.0;          
    p[2] =  3.0;            
    p[3] =  0.01 * currentscan->ff_xcenter; 
    p[4] =  0.01 * currentscan->ff_ycenter;
    //p[3] =  0.0; 
    //p[4] =  0.0; 

    //printf("ff_xcenter: %f\n",currentscan->ff_xcenter);
    
    p[5] =  0.0; 
    p[6] =  0.0; 
    
    if (DEBUGGING) {
      fprintf(stderr,"Calling frprmn()\n");
    }
    frprmn(p, nterms_amplitude, ftol, &iter_amp, &fret_amp, functionamp, dfunctionamp);   
    if (DEBUGGING) {
      fprintf(stderr,"number of amp iterations = %d\n", iter_amp);
    }

    
    currentscan->ampfit_amp = p[1];
    currentscan->ampfit_width_deg = p[2];
    currentscan->ampfit_u_off_deg = p[3];
    currentscan->ampfit_v_off_deg = p[4];
    currentscan->ampfit_d_0_90 = p[5];
    currentscan->ampfit_d_45_135 = p[6];
    
    /*
    printf("amp   = %f\n",p[1]);
    printf("ampw  = %f\n",p[2]);
    printf("uoff  = %f\n",p[3]);
    printf("voff  = %f\n",p[4]);
    printf("d090  = %f\n",p[5]);
    printf("d45135= %f\n",p[6]);
    getchar();
    */
    
    return 1;
}


float ComputeAmplitudeEfficiency(float p[]) {
  float amp_mod_term,amp_diff_term;
  float E;
  int i;
  
  float amp_fit = 0.0;
  for (i = 0; i<ampfitscan->ff_pts - 1; i++) {
 
    if (ampfitscan->mask[i] > 0.0){
        E = ampfitscan->E[i];
        amp_mod_term = p[1]*(1-pow((1-exp(-(1/pow(p[2],2.0))*(pow((ampfitscan->x[i]-p[3]),
        2.0)+pow((ampfitscan->y[i]-p[4]),2.0)-p[5]*(pow((ampfitscan->x[i]-p[3]),2.0)-pow((ampfitscan->y[i]-p[4]),
        2.0))-2.0*p[6]*(ampfitscan->x[i]-p[3])*(ampfitscan->y[i]-p[4])))),2.0));
        
        amp_diff_term = ampfitscan->mask[i]*(E-amp_mod_term);
        amp_fit += pow(amp_diff_term,2.0);

    }//end if (mymask[i] > 0.0)
  }//end for (i = 1; i<=npts; i++)

  return(amp_fit);
}//end function


void dfunction_amp(float p[], float df[]) {
/* This function should compute the gradients of the chi-squared function,
 * which are stored in the array "df". Since it is not a analytic function, 
 * we must compute the partial derivatives numerically, which is done using:
 *    d(chi-square) / dp[j] = *chiSquare(p[j]+del) - chiSquare(p[j])) / del
 *   
 */
  int i,j;
  float par[nterms_amplitude+1];
  float delta = 0.01, del;
  
  for (j=1; j<=nterms_amplitude; j++) {
    /* set all the parameters back to the current values */
    for (i=1; i<=nterms_amplitude; i++) {
      par[i] = p[i];
    }
    /* apply a small offset to the parameter being adjusted this time through the loop */
    if (fabs(par[j]) > (delta/100000)) {
      del = delta*par[j];
    } else {
      /* this takes care of the unique case where the initial guess is zero */
      del = delta;
    }
    par[j] += del;
    df[j] = ((ComputeAmplitudeEfficiency(par)) - (ComputeAmplitudeEfficiency(p)))/del;
  }
}


float function_amp(float p[]) {
  /* This function should return the chi-squared value for the current model 
   * parameters that are passed in as an argument.  In this case, we want
   * to minimize the phase "inefficiency", so we simply calculate that.
   */
  return (ComputeAmplitudeEfficiency(p));

}
