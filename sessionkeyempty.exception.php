<?php
/**
 * Wird geworfen, wenn eine Session schon benutzt wird.
 *
 * @author Uwe L. Korn <uwelk@xhochy.org>
 */
class SessionKeyEmptyException extends XYException
{
    public function __construct($message, $code = 0)
    {
        parent::__construct($message, $code);
    }
    
    protected function GetMailSubject()
    {
        return 'SessionKeyEmptyException in file '.$this->aTrace[0]['File']
            .' at line '.$this->aTrace[0]['Line'];
    }

    protected function GetMailBody()
    {
        $sResult = 'SessionKeyEmptyException in file '.$this->aTrace[0]['File']
            .' at line '.$this->aTrace[0]['Line']."\n";
        $sResult.= "\nTrace:\n";
        $sResult.= $this->TraceToString();
        $sResult.= "\nVars:\n";
        $sResult.= $this->VarsToString();
        return $sResult;
    }
}
