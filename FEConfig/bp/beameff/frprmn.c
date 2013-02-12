#include <math.h>
#include <stdio.h>
#define NRANSI
#include "nrutil.h"
#define ITMAX 200
#define EPS 1.0e-10
#define FREEALL free_vector(xi,1,n);free_vector(h,1,n);free_vector(g,1,n);
extern int DEBUGGING;

void frprmn(float p[], int n, float ftol, int *iter, float *fret,
	float (*func)(float []), void (*dfunc)(float [], float []))
{
        void linmin(float p[], float xi[], int n, float *fret,
		float (*func)(float []));
	int j,its;
	float gg,gam,fp,dgg;
	float *g,*h,*xi;

	if (DEBUGGING) {
	  fprintf(stderr,"Entered frprmn with n=%d\n",n);	
	}
	g=vector(1,n);
	h=vector(1,n);
	xi=vector(1,n);
	if (DEBUGGING) {
	  fprintf(stderr,"Done vector(1,%d)\n",n);	
	}
	fp=(*func)(p);
	if (DEBUGGING) {
	  fprintf(stderr,"set fp=(*func)(p)\n");	
	}
	(*dfunc)(p,xi);
	if (DEBUGGING) {
	  fprintf(stderr,"done *dfunc\n");	
	}
	
	for (j=1;j<=n;j++) {
		g[j] = -xi[j];
		xi[j]=h[j]=g[j];
	}
	if (DEBUGGING) {
	  fprintf(stderr,"Done j=1..n\n");	
	}
	for (its=1;its<=ITMAX;its++) {
		*iter=its;
		if (DEBUGGING) {
		  fprintf(stderr,"Calling linmin\n");	
		}
		linmin(p,xi,n,fret,func);
		if (DEBUGGING) {
		  fprintf(stderr,"returned from linmin\n");	
		}
		
		
		if (2.0*fabs(*fret-fp) <= ftol*(fabs(*fret)+fabs(fp)+EPS)) {
			FREEALL
			  if (DEBUGGING) {
			    fprintf(stderr,"returning from frprmn\n");	
			  }
			return;
		}
		fp= *fret;
		(*dfunc)(p,xi);
		dgg=gg=0.0;
		for (j=1;j<=n;j++) {
			gg += g[j]*g[j];
			dgg += (xi[j]+g[j])*xi[j];
		}
		if (gg == 0.0) {
			FREEALL
			  if (DEBUGGING) {
			    fprintf(stderr,"2:returning from frprmn\n");	
			  }
			return;
		}
		gam=dgg/gg;
		for (j=1;j<=n;j++) {
			g[j] = -xi[j];
			xi[j]=h[j]=g[j]+gam*h[j];
		}
	}
	nrerror("Too many iterations in frprmn");
}
#undef ITMAX
#undef EPS
#undef FREEALL
#undef NRANSI
