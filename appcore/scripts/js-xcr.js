function xcr_form_adjust()
{
	var xcrtype = document.getElementById('xcrtype').value;

    switch (xcrtype)
    {
        // Single Shot with ON times
        case "0":
                document.getElementById('ontime').disabled=false;
                document.getElementById('offtime').disabled=true;
                document.getElementById('onremaining').disabled=false;
                document.getElementById('offremaining').disabled=true;
                break;

        // Retriggerable with ON time
        case "1":
                document.getElementById('ontime').disabled=false;
                document.getElementById('offtime').disabled=true;
                document.getElementById('onremaining').disabled=false;
                document.getElementById('offremaining').disabled=true;
                break;

        // Follow Stimulus - No ON or OFF times
        case "2":
                document.getElementById('ontime').disabled=true;
                document.getElementById('offtime').disabled=true;
                document.getElementById('onremaining').disabled=true;
                document.getElementById('offremaining').disabled=true;
                break;

        // Retriggerable with ON and OFF times
        case "3":
                document.getElementById('ontime').disabled=false;
                document.getElementById('offtime').disabled=false;
                document.getElementById('onremaining').disabled=false;
                document.getElementById('offremaining').disabled=false;
                break;
             
        // Set/Reset type
        case "4":
                document.getElementById('ontime').disabled=true;
                document.getElementById('offtime').disabled=true;
                document.getElementById('onremaining').disabled=true;
                document.getElementById('offremaining').disabled=true;
                break;
    }
}