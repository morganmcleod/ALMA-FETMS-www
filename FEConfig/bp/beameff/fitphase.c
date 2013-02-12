#include <stdio.h>
#include <math.h>
#include "iniparser.h"
#include "utilities.h"
#include "fitphase.h"
#include "nr.h"
#include "constants.h"

float function_phase(float p[]);
void dfunction_phase(float p[], float df[]);
SCANDATA * phasefitscan;
const int nterms_phase = 3;

int FitPhase(SCANDATA *currentscan){
    //float ftol = 1.0e-5;
    float ftol;
    
    ftol = pow(10, -1.2*currentscan->band);

    int iter_phase; 
    float fret_phase;
    float p[nterms_phase+1];
  
    phasefitscan = currentscan;
    void (*dfunctionphase)(float p[], float df[]);
    float (*functionphase)(float p[]);

    functionphase = &function_phase;
    dfunctionphase = &dfunction_phase;

    // better way to FitPhase:
    //  first call with p = {0, 0, 0} and mask radius= 1 deg.
    //  find phase center in rad/deg.
    //  change mask to full subreflector ~3.8 deg.
    //  fimd phase center again using P from first iteration.



    //Initial guess values
//    p[1] = currentscan->az_nominal;
//    p[2] = currentscan->el_nominal;
//    p[3] = 200.0;

    p[1] = 0;
    p[2] = 0;
    p[3] = 260.0;
    
    // convert initial guess to rad/deg, rad/deg^2 for z:
    p[1] *=  0.001*(currentscan->k)*(2*PI/360.);           /* aka  slope_u */
    p[2] *=  0.001*(currentscan->k)*(2*PI/360.);           /* aka  slope_v */
    p[3] *= -0.001*(currentscan->k)*pow(2*PI/360.,2); 

    frprmn(p, nterms_phase, ftol, &iter_phase, &fret_phase, functionphase, dfunctionphase);   

    // convert back to mm:
    currentscan->delta_x = 1000.0*p[1]*(360.0/(2.0*PI))/currentscan->k;
    currentscan->delta_y = 1000.0*p[2]*(360.0/(2.0*PI))/currentscan->k;
    currentscan->delta_z = -1000.0*p[3]*pow(360.0/(2.0*PI),2.0)/currentscan->k;
    currentscan->eta_phase = 1-fret_phase;

    return 1;
}

float ComputePhaseEfficiency(float p[]){  
  float costerm, sinterm, eta_phase, normalizationFactor, phi_fit,phi_err,E;
  int i, phasefit_iter=0;

  costerm = sinterm = normalizationFactor = 0.0;

      for (i = 1; i<=phasefitscan->ff_pts; i++) {
 
        phi_fit = p[1]*phasefitscan->x[i] + p[2]*phasefitscan->y[i] + p[3]*phasefitscan->radius_squared[i]/2.0;
        phi_err = phasefitscan->ff_phase_rad[i] - phi_fit;
        E = phasefitscan->E[i];
        costerm += phasefitscan->mask[i]*E*cos(phi_err);
        sinterm += phasefitscan->mask[i]*E*sin(phi_err);
        normalizationFactor += phasefitscan->mask[i]*E;
      }
  eta_phase = (pow(costerm,2.0)+ pow(sinterm,2.0)) / pow(normalizationFactor,2.0);
  phasefit_iter +=1;
  
  
  return(eta_phase);
}//end function


void dfunction_phase(float p[], float df[]) {
/* This function should compute the gradients of the chi-squared function,
 * which are stored in the array "df". Since it is not a analytic function, 
 * we must compute the partial derivatives numerically, which is done using:
 *    d(chi-square) / dp[j] = *chiSquare(p[j]+del) - chiSquare(p[j])) / del
 *   
 */
  int i,j;
  float par[nterms_phase+1];
  float delta = 0.01, del;
  
  
  for (j=1; j<=nterms_phase; j++) {
    /* set all the parameters back to the current values */
    for (i=1; i<=nterms_phase; i++) {
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
    df[j] = ((1.0-ComputePhaseEfficiency(par)) - (1.0-ComputePhaseEfficiency(p)))/del;
  }
}


float function_phase(float p[]) {
  /* This function should return the chi-squared value for the current model 
   * parameters that are passed in as an argument.  In this case, we want
   * to minimize the phase "inefficiency", so we simply calculate that.
   */
  return (1.0-ComputePhaseEfficiency(p));

}
