SELECT TestData_header.keyId, TestData_header.TS
        FROM TestData_header, FE_Config
        WHERE TestData_header.DataSetGroup = 2
        AND TestData_header.fkTestData_Type = 7
        AND TestData_header.Band = 3
        AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
        AND FE_Config.fkFront_Ends = 91
        ORDER BY TestData_header.keyId ASC;
        
SELECT IFSpectrum_SubHeader.FreqLO, IFSpectrum_SubHeader.IFChannel, TEST_IFSpectrum_PowerVarFullBand.Power_dBm 
FROM IFSpectrum_SubHeader, TEST_IFSpectrum_PowerVarFullBand 
WHERE IFSpectrum_SubHeader.fkHeader IN (13648, 13649, 13650, 13651)
AND TEST_IFSpectrum_PowerVarFullBand.fkSubHeader = IFSpectrum_SubHeader.keyId 
AND IFSpectrum_SubHeader.IsIncluded = 1
AND IFSpectrum_SubHeader.IFGain = 15 
ORDER BY FreqLO, IFChannel ASC; 