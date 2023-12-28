<?php 
error_reporting(0);
ini_set('display_errors', 0);
init();
bootstrap();
date_default_timezone_set('America/Sao_Paulo');

function init()
{

    if(PHP_OS== "Linux")
    {
        define('python', 'python3.11');
    }
    else
    {
        define('python', 'python');
    }
}

function killPython()
{
    if(PHP_OS == "Linux")
    {
        shell_exec("killall -s 9 ".python);
    }
    else
    {
        exec("taskkill /IM python.exe /F");
    }
}

function getMemory()
{
    if(PHP_OS == 'Linux')
    {
        ///etc/sudoers
        //%www-data ALL=NOPASSWD: /sbin/reboot
       return  shell_exec('free -m');
    }
    else
    {
        return; #exec('shutdown /r /t 0');
    }
}


function getCPU()
{
    if(PHP_OS == 'Linux')
    {
        ///etc/sudoers
        //%www-data ALL=NOPASSWD: /sbin/reboot
        return shell_exec('iostat -c 1 1');
    }
    else
    {
        #exec('shutdown /r /t 0');
    }
}

function getHDD()
{
    if(PHP_OS == 'Linux')
    {
        return shell_exec('df -ht ext4');
    }
    else
    {
        #exec('shutdown /r /t 0');
    }
}


function forceReboot()
{
    if(PHP_OS == 'Linux')
    {
        ///etc/sudoers
        //%www-data ALL=NOPASSWD: /sbin/reboot
        shell_exec('sudo /sbin/reboot -r now');
    }
    else
    {
        exec('shutdown /r /t 0');
    }
}


function whois()
{
    killPython();
    $out =  shell_exec(python.' report.py');    
    $json =  json_decode($out, true);  
    return $json;
}


function bootstrap()
{
    if(!isset($_SERVER['whois']))
    {
        $whois = whois();
        $_SERVER['whois']=$whois;
    }
    $whois = $_SERVER['whois'];

    $mem = (string)getMemory();  
    $memo=  substr($mem, 88, strlen($mem));
    $memo = ltrim($memo);
    $memo = str_ireplace(' ',';',$memo);    
    $memo = explode(';;;;;;;', $memo);
    $total = $memo[0];
    $usada = ltrim($memo[1],';');
    $livre = floatval($memo[0])- floatval($usada);

    $memory= ' total:'.$total.'  usada:'.$usada.'  livre:'.$livre;
    print($memory);

    $cpu = getCPU();
    $cpu = ltrim($cpu);
    $cpus = substr($cpu, 130, strlen($cpu));
    $cpus = ltrim($cpus);
    $cpus = explode(" ",$cpus) ;

    $load = $cpus[0];
    $iddle = $cpus[19];
    $cpuUsage = ' load:'.$load.'   iddle:'.$iddle;

    #print($cpuUsage);
    echo PHP_EOL;
    $disk = getHDD();
    $disk = ltrim($disk);
    $hdd = substr($disk,  intval(strlen($disk)-7) , intval(strlen($disk)) );
    $htt = str_replace('/','',$hdd);
    #echo $htt;

    if(floatval($load)>floatval('95.00'))
    {
        sendData($memory, $cpuUsage, $htt,'alto uso de cpu reboot imediato',$whois);
        sleep(1);
        forceReboot();
        sleep(1);
        forceReboot();
    }

    if(floatval($usada/$total)>floatval(0.80))
    {
        sendData($memory, $cpuUsage, $htt,'alto uso de memoria reboot imediato',$whois);
        sleep(1);
        forceReboot();
        sleep(1);
        forceReboot();
    }
    if(testConnectivity() == true)
    {
        sendData($memory, $cpuUsage, $htt,'normal',$whois);
    }
  
}
function testConnectivity()
{
    if( @fopen("https://www.codespeedy.com/check-internet-connection-in-php/", "r"))
    {
        return 'connected';
    }
    else return 'offline';
}


function sendData($memory, $cpu, $hdd,$type, $whois)
{
    $postdata = http_build_query(
        array(
            'memory' => $memory,
            'cpu' => $cpu,
            'hdd' => $hdd,
            'tipo' => $type,
            'csrf' => md5(time()),
            'whois' => $whois
        )
    );
    
    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );
    
    $context  = stream_context_create($opts);
    
    $result = file_get_contents('https://boe-php.eletromidia.com.br/report/report.php', false, $context);
}
?>