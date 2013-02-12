/* note #undef's at end of file */
#define NRANSI
#include <stdio.h>
#include "nrutil.h"
#define TOL 2.0e-4
extern int DEBUGGING;
int ncom;
float *pcom,*xicom,(*nrfunc)(float []);

void linmin(float p[], float xi[], int n, float *fret, float (*func)(float []))
{
	float brent(float ax, float bx, float cx,
		float (*f)(float), float tol, float *xmin);
	float f1dim(float x);
	void mnbrak(float *ax, float *bx, float *cx, float *fa, float *fb,
		float *fc, float (*func)(float));
	int j;
	float xx,xmin,fx,fb,fa,bx,ax;
	
	if (DEBUGGING) {
	  fprintf(stderr,"Inside linmin with n=%d\n",n);
	}
	ncom=n;
	if (DEBUGGING) {
	  fprintf(stderr,"Calling vector(1,%d)\n",n);
	}
	pcom=vector(1,n);
	if (DEBUGGING) {
	  fprintf(stderr,"done vector(1,%d)\n",n);
	}
	xicom=vector(1,n);
	if (DEBUGGING && 0) {
	  fprintf(stderr,"done vector(1,%d)\n",n);
	}
	nrfunc=func;
	for (j=1;j<=n;j++) {
		pcom[j]=p[j];
		xicom[j]=xi[j];
	}
	ax=0.0;
	xx=1.0;
	if (DEBUGGING && 0) {
	  fprintf(stderr,"calling mnbrak\n");
	}
	mnbrak(&ax,&xx,&bx,&fa,&fx,&fb,f1dim);
	if (DEBUGGING && 0) {
	  fprintf(stderr,"returned from mnbrak\n");
	}

	*fret=brent(ax,xx,bx,f1dim,TOL,&xmin);
	if (DEBUGGING) {
	  fprintf(stderr,"returned from brent\n");
	}
	
	for (j=1;j<=n;j++) {
		xi[j] *= xmin;
		p[j] += xi[j];
	}
	free_vector(xicom,1,n);
	free_vector(pcom,1,n);
}
#undef TOL
#undef NRANSI
