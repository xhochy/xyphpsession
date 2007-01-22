<?php
/**
 * Main file of the XYSession-Library
 *
 * @package XYSession
 * @author Uwe L. Korn <uwelk@xhochy.org>
 */

/**
 * Stores Information for a certain time
 *
 * @package XYSession
 * @author Uwe L. Korn <uwelk@xhochy.org>
 */
class CSession
{
    public function __construct($sID)
    {
        Global $_CONFIG;
        $sID = basename($sID);
        //Lock
        if(!@symlink('locked',$_CONFIG['SessionDir'].'/'.$sID.'.session-lock'))
        {
            $oException = new SessionAlreadyInUseException($_CONFIG['SessionDir'].'/'.$sID.'.session');
            $oException->AddTrace(__FILE__, __LINE__, __FUNCTION__, __CLASS__);
            $oException->AddVar('filename',$_CONFIG['SessionDir'].'/'.$sID.'.session');
            $oException->AddVar('SessionID',$sID);
            throw $oException;
        }
        //TimeOut?
        if(CSession::TimedOut($sID))
        {
            CSession::RemoveSession($sID);
            $oException = new SessionTimedOutException($sID);
            $oException->AddTrace(__FILE__, __LINE__, __FUNCTION__, __CLASS__);
            $oException->AddVar('filename',$_CONFIG['SessionDir'].'/'.$sID.'.session');
            $oException->AddVar('SessionID',$sID);
            throw $oException;
        }
        //Load
        if(!($this->data = simplexml_load_file($_CONFIG['SessionDir'].'/'.$sID.'.session')))
        {
            $oException = new SessionNotLoadableException($_CONFIG['SessionDir'].'/'.$sID.'.session');
            $oException->AddTrace(__FILE__, __LINE__, __FUNCTION__, __CLASS__);
            $oException->AddVar('filename',$_CONFIG['SessionDir'].'/'.$sID.'.session');
            $oException->AddVar('SessionID',$sID);
            throw $oException;
        }
        $this->sID = $sID;
    }

    /**
     * Ein SimpleXML-Objekt, welches die Daten der Session enthaelt
     * 
     * @author Uwe L. Korn <uwelk@xhochy.org>
     * @var SimpleXML
     */
    private $data;

    /** 
     * Die ID der Session
     * 
     * @author Uwe L. Korn
     * @var string
     */
    private $sID;

    /**
     * Sucht eine freie ID für eine neue Session heraus und erstellt diese
     * 
     * @author Uwe L. Korn <uwelk@xhochy.org>
     * @param string $sChunk Data to create a new Session ID
     * @return CSession
     */
    public static function Create($sChunk)
    {
        Global $_CONFIG;
        $sID = sha1($sChunk);
        do {
            $sID = sha1($sChunk.$sID);
        } while (CSession::Exists($sID));

        //Create the Session with a lifetime of 1h
        $hFile = fopen($_CONFIG['SessionDir'].'/'.$sID.'.session','w');
        $sNew = "<?xml version=\"1.0\"?>\n<session>\n"
            ."<lifetime refreshable=\"true\">3600</lifetime>"
            ."<creationtime>".time()."</creationtime></session>";
        fwrite($hFile, $sNew);
        fclose($hFile);
        return new CSession($sID);
    }

    /**
     * Schaut nach ob eine Session mit der jeweiligen ID schon exitiert, 
     * wenn ja, wird ueberprueft, ob diese noch gueltig ist, wenn nicht,
     * wird diese geloescht
     *
     * @author Uwe L. Korn <uwelk@xhochy.org>
     * @param string $sID 
     * @return bool
     */
    public static function Exists($sID)
    {
        Global $_CONFIG;

        $sID = basename($sID);

        if(file_exists($_CONFIG['SessionDir'].'/'.$sID.'.session'))
        {
            if(CSession::TimedOut($sID))
            {
                if(CSession::Locked($sID)) 
                    return true;
                CSession::RemoveSession($sID);
                return false;
            }
            else return true;
        }
        else return false;// does not exist
    }

    /**
     * Schaut nach, ob eine Session noch gueltig ist
     *
     * @author Uwe L. Korn
     * @param string $sID
     * @return bool
     */
    public static function TimedOut($sID)
    {
        Global $_CONFIG;
        
        $sID = basename($sID);

        if(($oXML = @simplexml_load_file($_CONFIG['SessionDir'].'/'.$sID.'.session')) == false)
            return true;
        $nLifetime = (int)((string)($oXML->lifetime));
        $bRefresh = ((string)($oXML->lifetime['refreshable'])) == 'true';
        if($bRefresh)
            $nStartTime = fileatime($_CONFIG['SessionDir'].'/'.$sID.'.session');
        else
            $nStartTime = (int)((string)($oXML->creationtime));

        // Noch Lebenszeit?
        if($nStartTime + $nLifetime > time()) 
            return false;
        else
            return true;
    }

    /**
     * Loescht eine Datei, danach falls vorhanden auch noch den Lock
     * Es ist egal, ob die Datei gelocked ist.
     *
     * @author Uwe L. Korn <uwelk@xhochy.org>
     * @param string $sID
     */
    public static function RemoveSession($sID)
    {
        Global $_CONFIG;

        $sID = basename($sID);

        @unlink($_CONFIG['SessionDir'].'/'.$sID.'.session');
        @unlink($_CONFIG['SessionDir'].'/'.$sID.'.session-lock');
    }

    /**
     * Fuegt einen Eintrag in der Session hinzu
     *
     * @author Uwe L. Korn <uwelk@xhochy.org>
     * @param string $sKey
     * @param string $sValue
     */
    public function Add($sKey, $sValue)
    {
        $this->data->addChild($sKey, $sValue);
    }

    /**
     * Liest einen Eintrag aus der Session
     * 
     * @author Uwe L. Korn <uwelk@xhochy.org>
     * @param string $sKey
     * @return string
     */
    public function Read($sKey)
    {
        if(isset($this->data->$sKey))
            return (string)$this->data->$sKey;
        else
        {
            $oException = new SessionKeyEmptyException($sKey);     
            $oException->AddTrace(__FILE__, __LINE__, __FUNCTION__, __CLASS__);
            $oException->AddVars('Key',$sKey);
            throw $oException;
        }
    }

    /**
     * Ueberprueft, ob der Eintrag existiert
     * 
     * @author Uwe L. Korn <uwelk@xhochy.org>
     * @param string $sKey
     * @return bool
     */
    public function KeysExists($sKey)
    {
        return isset($this->data->$sKey);
    }

    /**
     * Schaut nach, ob die Session gesperrt ist
     * 
     * @author Uwe L. Korn <uwelk@xhochy.org>
     * @param string $sID Die ID der Session
     * @return mixed false if not locked, else 'locked'
     */
    public static function Locked($sID)
    {
        Global $_CONFIG;

        $sID = basename($sID);

        return @readlink($_CONFIG['SessionDir'].'/'.$sID.'.session-lock');
    }

    /**
     * Es wird die Zeit seit der Erstellung berechnet, anstatt der Zeit seit dem letzten Zugriff
     * 
     * @author Uwe L. Korn <uwelk@xhochy.org>
     */
    public function NoLifetimeRefresh()
    {
        $this->data->lifetime['refreshable'] = 'false';
    }

    /**
     * Setzt die Lebenszeit der Session
     * 
     * @author Uwe L. Korn <uwelk@xhochy.org>
     * @param int $nLifetime
     */
    public function SetLifetime($nLifetime)
    {
        $this->data->lifetime = (string)$nLifetime;
    }

    /**
     * Gibt die ID der Session zurueck
     *
     * @author Uwe L. Korn <uwelk@xhochy.org>
     * @return string
     */
    public function ID()
    {
        return $this->sID;
    }

    function __destruct()
    {
        Global $_CONFIG;
        @file_put_contents($_CONFIG['SessionDir'].'/'.$this->sID.'.session', $this->data->asXML());
        @unlink($_CONFIG['SessionDir'].'/'.$this->sID.'.session-lock');
    }
}
