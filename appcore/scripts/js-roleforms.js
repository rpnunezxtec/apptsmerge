var hD="0123456789ABCDEF";

function d2h(d) 
{
	var h = hD.substr(d&15,1);
	while ((d>15)||(d<-15))
	{
		d>>>=4;
		h=hD.substr(d&15,1)+h;
	}
	return h;
}

function h2d(h)
{
	var d=parseInt(h,16);
	return d;
}

function updateReadmaskbits()
{
	var maskval;
	
	maskval=h2d(document.forms[0].readmask.value);
	
	if (maskval & 0x00000001)
		document.forms[0].rm0.checked=true;
	else
		document.forms[0].rm0.checked=false;
	if (maskval & 0x00000002)
		document.forms[0].rm1.checked=true;
	else
		document.forms[0].rm1.checked=false;
	if (maskval & 0x00000004)
		document.forms[0].rm2.checked=true;
	else
		document.forms[0].rm2.checked=false;
	if (maskval & 0x00000008)
		document.forms[0].rm3.checked=true;
	else
		document.forms[0].rm3.checked=false;
	if (maskval & 0x00000010)
		document.forms[0].rm4.checked=true;
	else
		document.forms[0].rm4.checked=false;
	if (maskval & 0x00000020)
		document.forms[0].rm5.checked=true;
	else
		document.forms[0].rm5.checked=false;
	if (maskval & 0x00000040)
		document.forms[0].rm6.checked=true;
	else
		document.forms[0].rm6.checked=false;
	if (maskval & 0x00000080)
		document.forms[0].rm7.checked=true;
	else
		document.forms[0].rm7.checked=false;
	if (maskval & 0x00000100)
		document.forms[0].rm8.checked=true;
	else
		document.forms[0].rm8.checked=false;
	if (maskval & 0x00000200)
		document.forms[0].rm9.checked=true;
	else
		document.forms[0].rm9.checked=false;
	if (maskval & 0x00000400)
		document.forms[0].rm10.checked=true;
	else
		document.forms[0].rm10.checked=false;
	if (maskval & 0x00000800)
		document.forms[0].rm11.checked=true;
	else
		document.forms[0].rm11.checked=false;
	if (maskval & 0x00001000)
		document.forms[0].rm12.checked=true;
	else
		document.forms[0].rm12.checked=false;
	if (maskval & 0x00002000)
		document.forms[0].rm13.checked=true;
	else
		document.forms[0].rm13.checked=false;
	if (maskval & 0x00004000)
		document.forms[0].rm14.checked=true;
	else
		document.forms[0].rm14.checked=false;
	if (maskval & 0x00008000)
		document.forms[0].rm15.checked=true;
	else
		document.forms[0].rm15.checked=false;
	if (maskval & 0x00010000)
		document.forms[0].rm16.checked=true;
	else
		document.forms[0].rm16.checked=false;
	if (maskval & 0x00020000)
		document.forms[0].rm17.checked=true;
	else
		document.forms[0].rm17.checked=false;
	if (maskval & 0x00040000)
		document.forms[0].rm18.checked=true;
	else
		document.forms[0].rm18.checked=false;
	if (maskval & 0x00080000)
		document.forms[0].rm19.checked=true;
	else
		document.forms[0].rm19.checked=false;
	if (maskval & 0x00100000)
		document.forms[0].rm20.checked=true;
	else
		document.forms[0].rm20.checked=false;
	if (maskval & 0x00200000)
		document.forms[0].rm21.checked=true;
	else
		document.forms[0].rm21.checked=false;
	if (maskval & 0x00400000)
		document.forms[0].rm22.checked=true;
	else
		document.forms[0].rm22.checked=false;
	if (maskval & 0x00800000)
		document.forms[0].rm23.checked=true;
	else
		document.forms[0].rm23.checked=false;
	if (maskval & 0x01000000)
		document.forms[0].rm24.checked=true;
	else
		document.forms[0].rm24.checked=false;
	if (maskval & 0x02000000)
		document.forms[0].rm25.checked=true;
	else
		document.forms[0].rm25.checked=false;
	if (maskval & 0x04000000)
		document.forms[0].rm26.checked=true;
	else
		document.forms[0].rm26.checked=false;
	if (maskval & 0x08000000)
		document.forms[0].rm27.checked=true;
	else
		document.forms[0].rm27.checked=false;
	if (maskval & 0x10000000)
		document.forms[0].rm28.checked=true;
	else
		document.forms[0].rm28.checked=false;
	if (maskval & 0x20000000)
		document.forms[0].rm29.checked=true;
	else
		document.forms[0].rm29.checked=false;
	if (maskval & 0x40000000)
		document.forms[0].rm30.checked=true;
	else
		document.forms[0].rm30.checked=false;
	if (maskval & 0x80000000)
		document.forms[0].rm31.checked=true;
	else
		document.forms[0].rm31.checked=false;
}

function updateWritemaskbits()
{
	var maskval;
	
	maskval=h2d(document.forms[0].writemask.value);
	
	if (maskval & 0x00000001)
		document.forms[0].wm0.checked=true;
	else
		document.forms[0].wm0.checked=false;
	if (maskval & 0x00000002)
		document.forms[0].wm1.checked=true;
	else
		document.forms[0].wm1.checked=false;
	if (maskval & 0x00000004)
		document.forms[0].wm2.checked=true;
	else
		document.forms[0].wm2.checked=false;
	if (maskval & 0x00000008)
		document.forms[0].wm3.checked=true;
	else
		document.forms[0].wm3.checked=false;
	if (maskval & 0x00000010)
		document.forms[0].wm4.checked=true;
	else
		document.forms[0].wm4.checked=false;
	if (maskval & 0x00000020)
		document.forms[0].wm5.checked=true;
	else
		document.forms[0].wm5.checked=false;
	if (maskval & 0x00000040)
		document.forms[0].wm6.checked=true;
	else
		document.forms[0].wm6.checked=false;
	if (maskval & 0x00000080)
		document.forms[0].wm7.checked=true;
	else
		document.forms[0].wm7.checked=false;
	if (maskval & 0x00000100)
		document.forms[0].wm8.checked=true;
	else
		document.forms[0].wm8.checked=false;
	if (maskval & 0x00000200)
		document.forms[0].wm9.checked=true;
	else
		document.forms[0].wm9.checked=false;
	if (maskval & 0x00000400)
		document.forms[0].wm10.checked=true;
	else
		document.forms[0].wm10.checked=false;
	if (maskval & 0x00000800)
		document.forms[0].wm11.checked=true;
	else
		document.forms[0].wm11.checked=false;
	if (maskval & 0x00001000)
		document.forms[0].wm12.checked=true;
	else
		document.forms[0].wm12.checked=false;
	if (maskval & 0x00002000)
		document.forms[0].wm13.checked=true;
	else
		document.forms[0].wm13.checked=false;
	if (maskval & 0x00004000)
		document.forms[0].wm14.checked=true;
	else
		document.forms[0].wm14.checked=false;
	if (maskval & 0x00008000)
		document.forms[0].wm15.checked=true;
	else
		document.forms[0].wm15.checked=false;
	if (maskval & 0x00010000)
		document.forms[0].wm16.checked=true;
	else
		document.forms[0].wm16.checked=false;
	if (maskval & 0x00020000)
		document.forms[0].wm17.checked=true;
	else
		document.forms[0].wm17.checked=false;
	if (maskval & 0x00040000)
		document.forms[0].wm18.checked=true;
	else
		document.forms[0].wm18.checked=false;
	if (maskval & 0x00080000)
		document.forms[0].wm19.checked=true;
	else
		document.forms[0].wm19.checked=false;
	if (maskval & 0x00100000)
		document.forms[0].wm20.checked=true;
	else
		document.forms[0].wm20.checked=false;
	if (maskval & 0x00200000)
		document.forms[0].wm21.checked=true;
	else
		document.forms[0].wm21.checked=false;
	if (maskval & 0x00400000)
		document.forms[0].wm22.checked=true;
	else
		document.forms[0].wm22.checked=false;
	if (maskval & 0x00800000)
		document.forms[0].wm23.checked=true;
	else
		document.forms[0].wm23.checked=false;
	if (maskval & 0x01000000)
		document.forms[0].wm24.checked=true;
	else
		document.forms[0].wm24.checked=false;
	if (maskval & 0x02000000)
		document.forms[0].wm25.checked=true;
	else
		document.forms[0].wm25.checked=false;
	if (maskval & 0x04000000)
		document.forms[0].wm26.checked=true;
	else
		document.forms[0].wm26.checked=false;
	if (maskval & 0x08000000)
		document.forms[0].wm27.checked=true;
	else
		document.forms[0].wm27.checked=false;
	if (maskval & 0x10000000)
		document.forms[0].wm28.checked=true;
	else
		document.forms[0].wm28.checked=false;
	if (maskval & 0x20000000)
		document.forms[0].wm29.checked=true;
	else
		document.forms[0].wm29.checked=false;
	if (maskval & 0x40000000)
		document.forms[0].wm30.checked=true;
	else
		document.forms[0].wm30.checked=false;
	if (maskval & 0x80000000)
		document.forms[0].wm31.checked=true;
	else
		document.forms[0].wm31.checked=false;
}

function updateProcessmaskbits()
{
	var maskval;
	
	maskval=h2d(document.forms[0].processmask.value);
	
	if (maskval & 0x00000001)
		document.forms[0].pm0.checked=true;
	else
		document.forms[0].pm0.checked=false;
	if (maskval & 0x00000002)
		document.forms[0].pm1.checked=true;
	else
		document.forms[0].pm1.checked=false;
	if (maskval & 0x00000004)
		document.forms[0].pm2.checked=true;
	else
		document.forms[0].pm2.checked=false;
	if (maskval & 0x00000008)
		document.forms[0].pm3.checked=true;
	else
		document.forms[0].pm3.checked=false;
	if (maskval & 0x00000010)
		document.forms[0].pm4.checked=true;
	else
		document.forms[0].pm4.checked=false;
	if (maskval & 0x00000020)
		document.forms[0].pm5.checked=true;
	else
		document.forms[0].pm5.checked=false;
	if (maskval & 0x00000040)
		document.forms[0].pm6.checked=true;
	else
		document.forms[0].pm6.checked=false;
	if (maskval & 0x00000080)
		document.forms[0].pm7.checked=true;
	else
		document.forms[0].pm7.checked=false;
	if (maskval & 0x00000100)
		document.forms[0].pm8.checked=true;
	else
		document.forms[0].pm8.checked=false;
	if (maskval & 0x00000200)
		document.forms[0].pm9.checked=true;
	else
		document.forms[0].pm9.checked=false;
	if (maskval & 0x00000400)
		document.forms[0].pm10.checked=true;
	else
		document.forms[0].pm10.checked=false;
	if (maskval & 0x00000800)
		document.forms[0].pm11.checked=true;
	else
		document.forms[0].pm11.checked=false;
	if (maskval & 0x00001000)
		document.forms[0].pm12.checked=true;
	else
		document.forms[0].pm12.checked=false;
	if (maskval & 0x00002000)
		document.forms[0].pm13.checked=true;
	else
		document.forms[0].pm13.checked=false;
	if (maskval & 0x00004000)
		document.forms[0].pm14.checked=true;
	else
		document.forms[0].pm14.checked=false;
	if (maskval & 0x00008000)
		document.forms[0].pm15.checked=true;
	else
		document.forms[0].pm15.checked=false;
	if (maskval & 0x00010000)
		document.forms[0].pm16.checked=true;
	else
		document.forms[0].pm16.checked=false;
	if (maskval & 0x00020000)
		document.forms[0].pm17.checked=true;
	else
		document.forms[0].pm17.checked=false;
	if (maskval & 0x00040000)
		document.forms[0].pm18.checked=true;
	else
		document.forms[0].pm18.checked=false;
	if (maskval & 0x00080000)
		document.forms[0].pm19.checked=true;
	else
		document.forms[0].pm19.checked=false;
	if (maskval & 0x00100000)
		document.forms[0].pm20.checked=true;
	else
		document.forms[0].pm20.checked=false;
	if (maskval & 0x00200000)
		document.forms[0].pm21.checked=true;
	else
		document.forms[0].pm21.checked=false;
	if (maskval & 0x00400000)
		document.forms[0].pm22.checked=true;
	else
		document.forms[0].pm22.checked=false;
	if (maskval & 0x00800000)
		document.forms[0].pm23.checked=true;
	else
		document.forms[0].pm23.checked=false;
	if (maskval & 0x01000000)
		document.forms[0].pm24.checked=true;
	else
		document.forms[0].pm24.checked=false;
	if (maskval & 0x02000000)
		document.forms[0].pm25.checked=true;
	else
		document.forms[0].pm25.checked=false;
	if (maskval & 0x04000000)
		document.forms[0].pm26.checked=true;
	else
		document.forms[0].pm26.checked=false;
	if (maskval & 0x08000000)
		document.forms[0].pm27.checked=true;
	else
		document.forms[0].pm27.checked=false;
	if (maskval & 0x10000000)
		document.forms[0].pm28.checked=true;
	else
		document.forms[0].pm28.checked=false;
	if (maskval & 0x20000000)
		document.forms[0].pm29.checked=true;
	else
		document.forms[0].pm29.checked=false;
	if (maskval & 0x40000000)
		document.forms[0].pm30.checked=true;
	else
		document.forms[0].pm30.checked=false;
	if (maskval & 0x80000000)
		document.forms[0].pm31.checked=true;
	else
		document.forms[0].pm31.checked=false;
}


function updateReadmask()
{
	var i;
	var maskval;
	var s;
	var l;
	var z;
	
	// updates the readmask value in the readmask element
	// with the contents calculated from the checkboxes rm0 .. rm31
	maskval=0;
	if (document.forms[0].rm0.checked)
		maskval|=0x00000001;
	if (document.forms[0].rm1.checked)
		maskval|=0x00000002;
	if (document.forms[0].rm2.checked)
		maskval|=0x00000004;
	if (document.forms[0].rm3.checked)
		maskval|=0x00000008;
	if (document.forms[0].rm4.checked)
		maskval|=0x00000010;
	if (document.forms[0].rm5.checked)
		maskval|=0x00000020;
	if (document.forms[0].rm6.checked)
		maskval|=0x00000040;
	if (document.forms[0].rm7.checked)
		maskval|=0x00000080;
	if (document.forms[0].rm8.checked)
		maskval|=0x00000100;
	if (document.forms[0].rm9.checked)
		maskval|=0x00000200;
	if (document.forms[0].rm10.checked)
		maskval|=0x00000400;
	if (document.forms[0].rm11.checked)
		maskval|=0x00000800;
	if (document.forms[0].rm12.checked)
		maskval|=0x00001000;
	if (document.forms[0].rm13.checked)
		maskval|=0x00002000;
	if (document.forms[0].rm14.checked)
		maskval|=0x00004000;
	if (document.forms[0].rm15.checked)
		maskval|=0x00008000;
	if (document.forms[0].rm16.checked)
		maskval|=0x00010000;
	if (document.forms[0].rm17.checked)
		maskval|=0x00020000;
	if (document.forms[0].rm18.checked)
		maskval|=0x00040000;
	if (document.forms[0].rm19.checked)
		maskval|=0x00080000;
	if (document.forms[0].rm20.checked)
		maskval|=0x00100000;
	if (document.forms[0].rm21.checked)
		maskval|=0x00200000;
	if (document.forms[0].rm22.checked)
		maskval|=0x00400000;
	if (document.forms[0].rm23.checked)
		maskval|=0x00800000;
	if (document.forms[0].rm24.checked)
		maskval|=0x01000000;
	if (document.forms[0].rm25.checked)
		maskval|=0x02000000;
	if (document.forms[0].rm26.checked)
		maskval|=0x04000000;
	if (document.forms[0].rm27.checked)
		maskval|=0x08000000;
	if (document.forms[0].rm28.checked)
		maskval|=0x10000000;
	if (document.forms[0].rm29.checked)
		maskval|=0x20000000;
	if (document.forms[0].rm30.checked)
		maskval|=0x40000000;
	if (document.forms[0].rm31.checked)
		maskval|=0x80000000;
	
	s=d2h(maskval);
	l=s.length;
	z="";
	for (i=l; i<8; i++)
		z=z+"0";
	document.forms[0].readmask.value="0x"+z+s;
}

function updateWritemask()
{
	var i;
	var maskval;
	var s;
	var l;
	var z;
	
	// updates the writemask value in the writemask element
	// with the contents calculated from the checkboxes wm0 .. wm31
	maskval=0;
	if (document.forms[0].wm0.checked)
		maskval|=0x00000001;
	if (document.forms[0].wm1.checked)
		maskval|=0x00000002;
	if (document.forms[0].wm2.checked)
		maskval|=0x00000004;
	if (document.forms[0].wm3.checked)
		maskval|=0x00000008;
	if (document.forms[0].wm4.checked)
		maskval|=0x00000010;
	if (document.forms[0].wm5.checked)
		maskval|=0x00000020;
	if (document.forms[0].wm6.checked)
		maskval|=0x00000040;
	if (document.forms[0].wm7.checked)
		maskval|=0x00000080;
	if (document.forms[0].wm8.checked)
		maskval|=0x00000100;
	if (document.forms[0].wm9.checked)
		maskval|=0x00000200;
	if (document.forms[0].wm10.checked)
		maskval|=0x00000400;
	if (document.forms[0].wm11.checked)
		maskval|=0x00000800;
	if (document.forms[0].wm12.checked)
		maskval|=0x00001000;
	if (document.forms[0].wm13.checked)
		maskval|=0x00002000;
	if (document.forms[0].wm14.checked)
		maskval|=0x00004000;
	if (document.forms[0].wm15.checked)
		maskval|=0x00008000;
	if (document.forms[0].wm16.checked)
		maskval|=0x00010000;
	if (document.forms[0].wm17.checked)
		maskval|=0x00020000;
	if (document.forms[0].wm18.checked)
		maskval|=0x00040000;
	if (document.forms[0].wm19.checked)
		maskval|=0x00080000;
	if (document.forms[0].wm20.checked)
		maskval|=0x00100000;
	if (document.forms[0].wm21.checked)
		maskval|=0x00200000;
	if (document.forms[0].wm22.checked)
		maskval|=0x00400000;
	if (document.forms[0].wm23.checked)
		maskval|=0x00800000;
	if (document.forms[0].wm24.checked)
		maskval|=0x01000000;
	if (document.forms[0].wm25.checked)
		maskval|=0x02000000;
	if (document.forms[0].wm26.checked)
		maskval|=0x04000000;
	if (document.forms[0].wm27.checked)
		maskval|=0x08000000;
	if (document.forms[0].wm28.checked)
		maskval|=0x10000000;
	if (document.forms[0].wm29.checked)
		maskval|=0x20000000;
	if (document.forms[0].wm30.checked)
		maskval|=0x40000000;
	if (document.forms[0].wm31.checked)
		maskval|=0x80000000;
	
	s=d2h(maskval);
	l=s.length;
	z="";
	for (i=l; i<8; i++)
		z=z+"0";
	document.forms[0].writemask.value="0x"+z+s;
}

function updateProcessmask()
{
	var i;
	var maskval;
	var s;
	var l;
	var z;
	
	// updates the processmask value in the processmask element
	// with the contents calculated from the checkboxes pm0 .. pm31
	maskval=0;
	if (document.forms[0].pm0.checked)
		maskval|=0x00000001;
	if (document.forms[0].pm1.checked)
		maskval|=0x00000002;
	if (document.forms[0].pm2.checked)
		maskval|=0x00000004;
	if (document.forms[0].pm3.checked)
		maskval|=0x00000008;
	if (document.forms[0].pm4.checked)
		maskval|=0x00000010;
	if (document.forms[0].pm5.checked)
		maskval|=0x00000020;
	if (document.forms[0].pm6.checked)
		maskval|=0x00000040;
	if (document.forms[0].pm7.checked)
		maskval|=0x00000080;
	if (document.forms[0].pm8.checked)
		maskval|=0x00000100;
	if (document.forms[0].pm9.checked)
		maskval|=0x00000200;
	if (document.forms[0].pm10.checked)
		maskval|=0x00000400;
	if (document.forms[0].pm11.checked)
		maskval|=0x00000800;
	if (document.forms[0].pm12.checked)
		maskval|=0x00001000;
	if (document.forms[0].pm13.checked)
		maskval|=0x00002000;
	if (document.forms[0].pm14.checked)
		maskval|=0x00004000;
	if (document.forms[0].pm15.checked)
		maskval|=0x00008000;
	if (document.forms[0].pm16.checked)
		maskval|=0x00010000;
	if (document.forms[0].pm17.checked)
		maskval|=0x00020000;
	if (document.forms[0].pm18.checked)
		maskval|=0x00040000;
	if (document.forms[0].pm19.checked)
		maskval|=0x00080000;
	if (document.forms[0].pm20.checked)
		maskval|=0x00100000;
	if (document.forms[0].pm21.checked)
		maskval|=0x00200000;
	if (document.forms[0].pm22.checked)
		maskval|=0x00400000;
	if (document.forms[0].pm23.checked)
		maskval|=0x00800000;
	if (document.forms[0].pm24.checked)
		maskval|=0x01000000;
	if (document.forms[0].pm25.checked)
		maskval|=0x02000000;
	if (document.forms[0].pm26.checked)
		maskval|=0x04000000;
	if (document.forms[0].pm27.checked)
		maskval|=0x08000000;
	if (document.forms[0].pm28.checked)
		maskval|=0x10000000;
	if (document.forms[0].pm29.checked)
		maskval|=0x20000000;
	if (document.forms[0].pm30.checked)
		maskval|=0x40000000;
	if (document.forms[0].pm31.checked)
		maskval|=0x80000000;
	
	s=d2h(maskval);
	l=s.length;
	z="";
	for (i=l; i<8; i++)
		z=z+"0";
	document.forms[0].processmask.value="0x"+z+s;
}

function formrolesDeleteCheck()
{
    var x = confirm("Warning: Delete all roles from this form?");
    if (x)
      return true;
    else
      return false;
}