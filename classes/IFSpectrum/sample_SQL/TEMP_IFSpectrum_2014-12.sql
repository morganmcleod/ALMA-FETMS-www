DROP TEMPORARY TABLE IF EXISTS TEMP_IFSpectrum;

CREATE TEMPORARY TABLE TEMP_IFSpectrum (
	fkSubHeader INT,
	fkFacility INT,
	Freq_Hz DOUBLE,
	Power_dBm DOUBLE,
	INDEX (fkSubHeader))
SELECT IFSpectrum.fkSubHeader, IFSpectrum.fkFacility, IFSpectrum.Freq_Hz, IFSpectrum.Power_dBm
FROM FE_Config, TestData_header, IFSpectrum_SubHeader LEFT JOIN IFSpectrum
ON IFSpectrum_SubHeader.keyId = IFSpectrum.fkSubHeader
AND IFSpectrum_SubHeader.keyFacility = IFSpectrum.fkFacility
WHERE TestData_header.fkFE_Config = FE_Config.keyFEConfig
AND TestData_header.keyFacility = FE_Config.keyFacility
AND TestData_header.keyId = IFSpectrum_SubHeader.fkHeader
AND TestData_header.keyFacility = IFSpectrum_SubHeader.keyFacility
AND FE_Config.fkFront_Ends = 91
AND TestData_header.Band = 3 AND TestData_header.fkTestData_Type = 7
AND IFSpectrum_SubHeader.IsIncluded = 1 AND TestData_header.DataSetGroup = 2;

SELECT * from TEMP_IFSpectrum;