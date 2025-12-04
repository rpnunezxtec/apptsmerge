function checklogin(form)
{
        if ((isempty(form.userid)) || (isempty(form.passwd)))
        {
                return false;
        }
        else
        {
                return true;
        }
}

function isempty(mytext)
{
        var re = /^\s{1,}$/g;
        if ((mytext.value.length==0) || (mytext.value==null) || ((mytext.value.search(re)) > -1))
        {
                return true;
        }
        else
        {
                return false;
        }
}