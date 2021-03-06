(function() {
    function ajax(url, onsuccess, onfail) {
        var xmlHttp = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
        xmlHttp.open("GET", url, true);
        xmlHttp.onreadystatechange = function() {
            if (xmlHttp.readyState == 4) {
                if (xmlHttp.status == 200) {
                    onsuccess && onsuccess(xmlHttp.responseText)
                } else {
                    onfail && onfail(xmlHttp.status)
                }
            }
        };
        xmlHttp.send()
    }
    var js = document.getElementsByTagName("script");
    var logid = js[js.length - 1].getAttribute("logid");
    if (parseInt(logid) > 0) {
        ajax("/realclicks/" + logid)
    }
})();