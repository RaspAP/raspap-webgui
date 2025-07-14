function I(i){return document.getElementById(i);}

const origin=window.location.origin;
const host=window.location.host;
var SPEEDTEST_SERVERS=[
	{
		"name":"RaspAP Speedtest server (US)",
		"server":"https://speedtest.raspap.com/",
		"dlURL":"backend/garbage.php",
		"ulURL":"backend/empty.php",
		"pingURL":"backend/empty.php",
		"getIpURL":"backend/getIP.php"
	},
	{
		"name":"RaspAP ("+host+")",
		"server":origin,
		"dlURL":"dist/speedtest/backend/garbage.php",
		"ulURL":"dist/speedtest/backend/empty.php",
		"pingURL":"dist/speedtest/backend/empty.php",
		"getIpURL":"dist/speedtest/backend/getIP.php"
	}
];

//INITIALIZE SPEEDTEST
var s=new Speedtest(); //create speedtest object
s.setParameter("telemetry_level","basic"); //enable telemetry

//SERVER AUTO SELECTION
function initServers(){
    var noServersAvailable=function(){
        I("message").innerHTML="No servers available";
    }
    var runServerSelect=function(){
        s.selectServer(function(server){
            if(server!=null){ //at least 1 server is available
                I("loading").className="hidden"; //hide loading message
                //populate server list for manual selection
                for(var i=0;i<SPEEDTEST_SERVERS.length;i++){
                    //if(SPEEDTEST_SERVERS[i].pingT==-1) continue;
                    var option=document.createElement("option");
                    option.value=i;
                    option.textContent=SPEEDTEST_SERVERS[i].name;
                    if(SPEEDTEST_SERVERS[i]===server) option.selected=true;
                    I("server").appendChild(option);
                }
                //show test UI
                I("testWrapper").className="visible";
                initUI();
            }else{ //no servers are available, the test cannot proceed
                noServersAvailable();
            }
        });
    }
    if(typeof SPEEDTEST_SERVERS === "string"){
        //need to fetch list of servers from specified URL
        s.loadServerList(SPEEDTEST_SERVERS,function(servers){
            if(servers==null){ //failed to load server list
                noServersAvailable();
            }else{ //server list loaded
                SPEEDTEST_SERVERS=servers;
                runServerSelect();
            }
        });
    }else{
        //hardcoded server list
        s.addTestPoints(SPEEDTEST_SERVERS);
        runServerSelect();
    }
}

var meterBk=/Trident.*rv:(\d+\.\d+)/i.test(navigator.userAgent)?"#EAEAEA":"#80808040";
var dlColor="#4BC0C0",
	ulColor="#616161";
var progColor=meterBk;

//CODE FOR GAUGES
function drawMeter(c,amount,bk,fg,progress,prog){
	var ctx=c.getContext("2d");
	var dp=window.devicePixelRatio||1;
	var cw=c.clientWidth*dp, ch=c.clientHeight*dp;
	var sizScale=ch*0.0055;
	if(c.width==cw&&c.height==ch){
		ctx.clearRect(0,0,cw,ch);
	}else{
		c.width=cw;
		c.height=ch;
	}
	ctx.beginPath();
	ctx.strokeStyle=bk;
	ctx.lineWidth=12*sizScale;
	ctx.arc(c.width/2,c.height-58*sizScale,c.height/1.8-ctx.lineWidth,-Math.PI*1.1,Math.PI*0.1);
	ctx.stroke();
	ctx.beginPath();
	ctx.strokeStyle=fg;
	ctx.lineWidth=12*sizScale;
	ctx.arc(c.width/2,c.height-58*sizScale,c.height/1.8-ctx.lineWidth,-Math.PI*1.1,amount*Math.PI*1.2-Math.PI*1.1);
	ctx.stroke();
	if(typeof progress !== "undefined"){
		ctx.fillStyle=prog;
		ctx.fillRect(c.width*0.3,c.height-16*sizScale,c.width*0.4*progress,4*sizScale);
	}
}
function mbpsToAmount(s){
	return 1-(1/(Math.pow(1.3,Math.sqrt(s))));
}
function format(d){
    d=Number(d);
    if(d<10) return d.toFixed(2);
    if(d<100) return d.toFixed(1);
    return d.toFixed(0);
}

//UI CODE
var uiData=null;
function startStop(){
    if(s.getState()==3){
		//speedtest is running, abort
		s.abort();
		data=null;
		I("startStopBtn").className="btn btn-outline btn-primary";
		I("server").disabled=false;
		initUI();
	}else{
		//test is not running, begin
		I("startStopBtn").className="btn btn-outline btn-primary running";
		I("server").disabled=true;
		s.onupdate=function(data){
            uiData=data;
		};
		s.onend=function(aborted){
            I("startStopBtn").className="btn btn-outline btn-primary";
            I("server").disabled=false;
            updateUI(true);
            if(!aborted){
                //if testId is present, show sharing panel, otherwise do nothing
                try{
                    var testId=uiData.testId;
                    if(testId!=null){
                        var shareURL=window.location.href.substring(0,window.location.href.lastIndexOf("/"))+"/results/?id="+testId;
                        I("resultsImg").src=shareURL;
                        I("resultsURL").value=shareURL;
                        I("testId").innerHTML=testId;
                        I("shareArea").style.display="";
                    }
                }catch(e){}
            }
		};
		s.start();
	}
}
//this function reads the data sent back by the test and updates the UI
function updateUI(forced){
	if(!forced&&s.getState()!=3) return;
	if(uiData==null) return;
	var status=uiData.testState;
	I("ip").textContent=uiData.clientIp;
	I("dlText").textContent=(status==1&&uiData.dlStatus==0)?"...":format(uiData.dlStatus);
	drawMeter(I("dlMeter"),mbpsToAmount(Number(uiData.dlStatus*(status==1?oscillate():1))),meterBk,dlColor,Number(uiData.dlProgress),progColor);
	I("ulText").textContent=(status==3&&uiData.ulStatus==0)?"...":format(uiData.ulStatus);
	drawMeter(I("ulMeter"),mbpsToAmount(Number(uiData.ulStatus*(status==3?oscillate():1))),meterBk,ulColor,Number(uiData.ulProgress),progColor);
	I("pingText").textContent=format(uiData.pingStatus);
	I("jitText").textContent=format(uiData.jitterStatus);
}
function oscillate(){
	return 1+0.02*Math.sin(Date.now()/100);
}
//update the UI every frame
window.requestAnimationFrame=window.requestAnimationFrame||window.webkitRequestAnimationFrame||window.mozRequestAnimationFrame||window.msRequestAnimationFrame||(function(callback,element){setTimeout(callback,1000/60);});
function frame(){
	requestAnimationFrame(frame);
	updateUI();
}
frame(); //start frame loop
//function to (re)initialize UI
function initUI(){
	drawMeter(I("dlMeter"),0,meterBk,dlColor,0);
	drawMeter(I("ulMeter"),0,meterBk,ulColor,0);
	I("dlText").textContent="";
	I("ulText").textContent="";
	I("pingText").textContent="";
	I("jitText").textContent="";
	I("ip").textContent="";
}
// add EventListener to diagnostic tab
var e = document.querySelectorAll("a[href^='#diagnostic']");
e[0].addEventListener("click", function(){
    initServers()
});

