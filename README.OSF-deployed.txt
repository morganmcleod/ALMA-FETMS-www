The key files which are different on OSF-deployed as opposed to master are:

specs/FEIC_NoiseTemperature.ini
- cold load calibration TEff for the OSF cold load

SiteConfig.php
- error_reporting($errorReportSettingsNo_E_NOTICE);
- date_default_timezone_set('America/Santiago');


