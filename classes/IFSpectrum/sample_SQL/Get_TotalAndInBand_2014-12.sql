SELECT TestData_header.keyId, TestData_header.TS
        FROM TestData_header, FE_Config
        WHERE TestData_header.DataSetGroup = 2
        AND TestData_header.fkTestData_Type = 7
        AND TestData_header.Band = 3
        AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
        AND FE_Config.fkFront_Ends = 91
        ORDER BY TestData_header.keyId ASC;
        
SELECT DISTINCT(FreqLO) FROM IFSpectrum_SubHeader
WHERE fkHeader IN (13648, 13649, 13650, 13651)
 AND IsIncluded = 1 ORDER BY FreqLO ASC;
 
SELECT IFSpectrum_SubHeader.FreqLO, IFSpectrum_SubHeader.IFGain, 
TEST_IFSpectrum_TotalPower.TotalPower, TEST_IFSpectrum_TotalPower.InBandPower
FROM IFSpectrum_SubHeader, TEST_IFSpectrum_TotalPower 
WHERE IFSpectrum_SubHeader.fkHeader IN (13648, 13649, 13650, 13651)
AND TEST_IFSpectrum_TotalPower.fkSubHeader = IFSpectrum_SubHeader.keyId 
AND IFSpectrum_SubHeader.IsIncluded = 1 
AND IFSpectrum_SubHeader.IFChannel = 1
ORDER BY FreqLO, IFGain ASC;