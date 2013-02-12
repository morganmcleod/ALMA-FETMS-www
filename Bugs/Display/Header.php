<html>
<head>
<link rel="stylesheet" type="text/css" href="style.css" />
<link rel="stylesheet" type="text/css" href="buttons.css">
</head>
<body>
<div id="header">
		<div id="header_inside">
			<h1><span>Bugs</span></h1>
			<ul>

			<select name="ViewAll" id="ViewAll" onchange="window.location=this.options[this.selectedIndex].value">
				<option selected>Select..</option>
				<option value="http://www.cv.nrao.edu/php-internal/ntc/Tasks3/Config/MxrHome.php">Mixers</option>
				<option value="http://www.cv.nrao.edu/php-internal/ntc/Tasks3/Config/ChipFilter.php">Chips</option>
				<option value="http://www.cv.nrao.edu/php-internal/ntc/Tasks3/Config/preamphome.php">Preamps</option>
				<option value="http://www.cv.nrao.edu/php-internal/ntc/Tasks3/Config/MixerPreamps.php">Mixer Preamp</option>
				<option value="http://www.cv.nrao.edu/php-internal/ntc/Tasks3/Config/optimumbias.php">Optimum Bias</option>
				<option value="http://www.cv.nrao.edu/php-internal/ntc/Tasks3/Cartridge/SubAssembly.php">Sub Assembly</option>
				<option value="http://www.cv.nrao.edu/php-internal/ntc/Tasks3/PreampBias/AddNewPreampParam.php">Preamp Parameters</option>
				<option value="http://www.cv.nrao.edu/php-internal/ntc/Tasks3/patterns/beampatterns.php">Beam Patterns</option>
				<option value="http://www.cv.nrao.edu/php-internal/ntc/Tasks3/TestSystems/TestSystem.php">Test Systems</option>
				<optgroup label="--OTHER--">
				<option value="http://www.cv.nrao.edu/php-internal/ntc/Bugs/NewTask1.php">Bugs</option>
				</optgroup>
			</select>
			<a href="ShowBugs.php" class="button gray biground">
			<span>Home</span>
			</a>
			<a href="AddNewBug.php?modulekey=9" class="button gray biground">
			<span>Bugs/Enhancements</span>
			</a>
			<a href="Summary.php" class="button gray biground">
			<span>Summary</span>
				</a>


			</ul>		
		</div>
	</div> 
</body>
</html>


