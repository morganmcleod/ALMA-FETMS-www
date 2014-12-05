SELECT IFSpectrum_SubHeader.keyId AS keySH, IFSpectrum_SubHeader.FreqLO, TestData_header.keyId AS keyTDH
FROM IFSpectrum_SubHeader, TestData_header, FE_Config
WHERE IFSpectrum_SubHeader.fkHeader = TestData_header.keyId
AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
AND IFSpectrum_SubHeader.Band = 3
AND IFSpectrum_SubHeader.IFChannel = 0
AND IFSpectrum_SubHeader.IFGain = 15
AND IFSpectrum_SubHeader.IsIncluded = 1
AND FE_Config.fkFront_Ends = 91
AND TestData_header.DataSetGroup = 2
ORDER BY IFSpectrum_SubHeader.FreqLO ASC;

SELECT IFSpectrum_SubHeader.FreqLO, TEMP_IFSpectrum.Freq_Hz, TEMP_IFSpectrum.Power_dBm
FROM IFSpectrum_SubHeader, TEMP_IFSpectrum
WHERE TEMP_IFSpectrum.fkSubHeader = IFSpectrum_SubHeader.keyId
AND IFSpectrum_SubHeader.keyId in (23646, 23648, 23650, 23652, 23654)
ORDER BY FreqLO, Freq_Hz ASC;