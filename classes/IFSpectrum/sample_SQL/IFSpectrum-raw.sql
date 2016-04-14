DROP TEMPORARY TABLE IF EXISTS TEMP_IFSpectrum;

 CREATE TEMPORARY TABLE TEMP_IFSpectrum ( IFChannel TINYINT, Freq_LO DOUBLE, Freq_Hz 
 DOUBLE, Power_dBm DOUBLE) SELECT IFSpectrum_SubHeader.IFChannel, 
 IFSpectrum_SubHeader.FreqLO, IFSpectrum.Freq_Hz, IFSpectrum.Power_dBm FROM FE_Config, 
 TestData_header, IFSpectrum_SubHeader LEFT JOIN IFSpectrum ON IFSpectrum_SubHeader.keyId = 
 IFSpectrum.fkSubHeader AND IFSpectrum_SubHeader.keyFacility = IFSpectrum.fkFacility WHERE 
 TestData_header.fkFE_Config = FE_Config.keyFEConfig AND TestData_header.keyFacility = 
 FE_Config.keyFacility AND TestData_header.keyId = IFSpectrum_SubHeader.fkHeader AND 
 TestData_header.keyFacility = IFSpectrum_SubHeader.keyFacility AND IFSpectrum_SubHeader.IFGain = 15 AND
 FE_Config.fkFront_Ends = 111 AND TestData_header.Band = 2 AND TestData_header.fkTestData_Type = 7 AND 
 IFSpectrum_SubHeader.IsIncluded = 1 AND TestData_header.DataSetGroup;
 
 select * from TEMP_IFSpectrum;
 