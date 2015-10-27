#include "SCANDATA.h"
#include <math.h>
#include <stdlib.h>
#include <stdio.h>
#include <string.h>

extern int DEBUGGING;

void SCANDATA_init(SCANDATA *data) {
    if (data != NULL)
        memset(data, 0, sizeof(SCANDATA));
}

#define WHACK(P) if (P != NULL) free(P); P = NULL;

void SCANDATA_free(SCANDATA *data) {
    if (data != NULL) {
        WHACK(data -> ff_az);
        WHACK(data -> ff_el);
        WHACK(data -> ff_amp_db);
        WHACK(data -> ff_phase_deg);
        WHACK(data -> ff_phase_rad);
        WHACK(data -> nf_x);
        WHACK(data -> nf_y);
        WHACK(data -> nf_amp_db);
        WHACK(data -> nf_phase_deg);
        WHACK(data -> x);
        WHACK(data -> y);
        WHACK(data -> radius);
        WHACK(data -> radius_squared);
        WHACK(data -> E);
        WHACK(data -> mask);
        WHACK(data -> maskE);
        WHACK(data -> pow_sec);
        SCANDATA_init(data);
    }
}

int SCANDATA_allocateArrays(SCANDATA *data) {
    // farfield arrays:
	if (data != NULL) {
		data -> ff_az = malloc(sizeof(float) * data -> ff_pts);
		data -> ff_el = malloc(sizeof(float) * data -> ff_pts);
		data -> ff_amp_db = malloc(sizeof(float) * data -> ff_pts);
		data -> ff_phase_deg = malloc(sizeof(float) * data -> ff_pts);
		data -> ff_phase_rad = malloc(sizeof(float) * data -> ff_pts);
		data -> x = malloc(sizeof(float) * data -> ff_pts);
		data -> y = malloc(sizeof(float) * data -> ff_pts);
		data -> radius = malloc(sizeof(float) * data -> ff_pts);
		data -> radius_squared = malloc(sizeof(float) * data -> ff_pts);
		data -> mask = malloc(sizeof(float) * data -> ff_pts);
		data -> E = malloc(sizeof(float) * data -> ff_pts);
		// nearfield arrays:
		data -> nf_x = malloc(sizeof(float) * data -> nf_pts);
		data -> nf_y = malloc(sizeof(float) * data -> nf_pts);
		data -> nf_amp_db = malloc(sizeof(float) * data -> nf_pts);
		data -> nf_phase_deg = malloc(sizeof(float) * data -> nf_pts);
		return 0;
	} else {
		return -1;
	}
}

int SCANDATA_allocateArraysXpol(SCANDATA *data) {
    // reallocate farfield arrays for xpol:
	if (data != NULL) {
		WHACK(data -> ff_az);
		WHACK(data -> ff_el);
		WHACK(data -> ff_amp_db);
		WHACK(data -> ff_phase_deg);
		data -> ff_az = malloc(sizeof(float) * data -> ff_pts);
		data -> ff_el = malloc(sizeof(float) * data -> ff_pts);
		data -> ff_amp_db = malloc(sizeof(float) * data -> ff_pts);
		data -> ff_phase_deg = malloc(sizeof(float) * data -> ff_pts);
        WHACK(data -> nf_x);
        WHACK(data -> nf_y);
        WHACK(data -> nf_amp_db);
        WHACK(data -> nf_phase_deg);
		data -> nf_x = malloc(sizeof(float) * data -> nf_pts);
		data -> nf_y = malloc(sizeof(float) * data -> nf_pts);
		data -> nf_amp_db = malloc(sizeof(float) * data -> nf_pts);
		data -> nf_phase_deg = malloc(sizeof(float) * data -> nf_pts);
	    return 0;
	} else {
		return -1;
	}
}

int SCANDATA_computeSums(SCANDATA *data, float maskRadius) {
    // compute sums, sums of squares, and other metrics on the farfield data, using the provided maskRadius
    float  maxamp, inner, outer;
    long int i;

    maxamp = data -> max_ff_amp_db;    // TODO: is this correct?  Is this variable zero at this point?
    printf("SCANDATA_computeSums: maxamp=%f\n", maxamp);
    inner = maskRadius - (data -> ff_stepsize / 2.0);
    outer = maskRadius + (data -> ff_stepsize / 2.0);
    
    //Fill up mask and E arrays, get sums
    data -> sum_mask=0;
    data -> sum_E=0;
    data -> sum_maskE=0; 
    data -> sum_powsec=0; 
    data -> sumsq_E=0; 
    data -> sumsq_maskE=0; 
    data -> sumsq_powsec=0;     
    data -> sumEdge=0;
    data -> sumEdgeE=0;

    for(i = 0; i < data -> ff_pts; ++i) {
        // Compute E: array of electric field voltages from 2D pattern, normalized to peak = 1.0:
        data -> E[i] = pow(10.0, (data -> ff_amp_db[i] - maxamp) / 20.0);   

        // Compute mask of secondary reflector:
        if (data -> radius[i] > outer) {
            // zero outside the maskRadius angle
            data -> mask[i] = 0.0; 
        
        } else if (data -> radius[i] < inner) {
            // one inside the subreflector
            data -> mask[i] = 1.0;                                                 
        
        } else {
            // at points on the edge, a linear taper between 0 and 1:
            data -> mask[i] = (outer - data -> radius[i]) / data -> ff_stepsize; 
        }
       
        // accumulate sum of the mask:
        data -> sum_mask += data -> mask[i];

        // accumulate sum and sum of squares of the electric field voltage:
        data -> sum_E += data -> E[i];  
        data -> sumsq_E += pow(data -> E[i], 2.0);

        // accumulate sum and sum of squares of voltage on the secondary:
        data -> sum_maskE += data -> mask[i] * data -> E[i]; 
        data -> sumsq_maskE += pow(data -> mask[i] * data -> E[i], 2.0);

        // accumulate sum and sum of squares of power on the secondary:
        data -> sum_powsec += data -> mask[i] * pow(data -> E[i], 2.0); 
        data -> sumsq_powsec += pow(data -> mask[i] * pow(data -> E[i], 2.0), 2.0);  
        
        // accumulate unnormalized total power and power on subreflector:
        // TODO:  is this calculation redundant?   Is it correct?
        data -> sum_intensity += pow(10.0, data -> ff_amp_db[i] / 10.0);
        data -> sum_intensity_on_subreflector += data -> sum_intensity * data -> mask[i];  
        
        // accumulate number of data points and sum of electric field voltage falling on the edge of the secondary:
        if (pow(data -> radius[i] - maskRadius, 2.0) < pow(data -> ff_stepsize, 2.0)) {
            data -> sumEdge++;
            data -> sumEdgeE += data -> E[i];
        }                                                
    }
    
    if (DEBUGGING) {
        printf("data -> ff_pts: %d\n",data -> ff_pts);
        printf("data -> sum_maskE: %d\n",data -> sum_maskE);
    }

    // Taper efficiency (Amplitude efficiency in R.Hills paper) is the ratio of the illumination
    // of the secondary to a uniform illumination:
    data -> eta_taper = pow(data -> sum_maskE, 2.0) / (data -> sum_mask * data -> sum_powsec);

    // Spillover efficiency is the ratio of power on the secondary to total power:
    data -> eta_spillover = data -> sum_powsec / data -> sumsq_E;

    // Illumination efficiency is the product of taper and spillover efficiency:
    data -> eta_illumination = data -> eta_taper * data -> eta_spillover;

    // Power in dB on the "edge" of the secondary is computed from the average voltage in the edge region:
    data -> edge_dB = 20 * log10(data -> sumEdgeE / data -> sumEdge);
    return 1;
}

int SCANDATA_computeCrosspolSums(SCANDATA *crosspolscan, SCANDATA *copolscan) {
    // compute sums, sums of squares, and other metrics on the farfield crosspol data.
    float E, maskE, pow_sec, intensity;
    long int i;
    
    //Fill up mask and E arrays, get sums
    crosspolscan -> sum_E = 0;
    crosspolscan -> sum_intensity = 0; 
    crosspolscan -> sum_intensity_on_subreflector = 0; 
    crosspolscan -> sum_powsec = 0; 
    crosspolscan -> sumsq_E = 0; 
    crosspolscan -> sumsq_powsec = 0; 

    for(i = 0; i < crosspolscan -> ff_pts; ++i) {                            
        // Compute E: array of electric field voltages from 2D pattern, normalized to peak = 1.0 of copol scan:
        E = pow(10.0, (crosspolscan -> ff_amp_db[i] - copolscan -> max_ff_amp_db) / 20.0);

        // accumulate sum and sum of squares of the electric field voltage:
        crosspolscan -> sum_E += E;
        crosspolscan -> sumsq_E += pow(E, 2.0);
        
        // accumulate sum and sum of squares of voltage on the secondary:
        maskE = copolscan -> mask[i] * E;
        crosspolscan -> sum_maskE += maskE;
        crosspolscan -> sumsq_maskE += pow(maskE, 2.0);

        // accumulate sum and sum of squares of power on the secondary:
        pow_sec = copolscan -> mask[i] * (pow(E, 2.0));
        crosspolscan -> sum_powsec += pow_sec;
        crosspolscan -> sumsq_powsec += pow(pow_sec, 2.0);  
        
        // accumulate unnormalized total power and power on subreflector:
        // TODO:  is this calculation redundant?   Is it correct?
        intensity = pow(10.0, crosspolscan -> ff_amp_db[i] / 10.0);
        crosspolscan -> sum_intensity += intensity;
        crosspolscan -> sum_intensity_on_subreflector += intensity * copolscan -> mask[i];                      
    }

    //
    crosspolscan -> eta_spill_co_cross = (copolscan -> sum_powsec + crosspolscan -> sum_powsec) / (copolscan->sumsq_E + crosspolscan -> sumsq_E);
    crosspolscan -> eta_pol_on_secondary = (copolscan -> sum_powsec) / (copolscan->sum_powsec + crosspolscan -> sum_powsec);
    crosspolscan -> eta_pol_spill = crosspolscan -> eta_spill_co_cross * crosspolscan -> eta_pol_on_secondary;

    return 1;
}

