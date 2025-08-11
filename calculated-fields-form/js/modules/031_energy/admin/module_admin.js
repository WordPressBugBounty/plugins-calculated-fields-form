fbuilderjQuery = (typeof fbuilderjQuery != 'undefined' ) ? fbuilderjQuery : jQuery;
fbuilderjQuery[ 'fbuilder' ] = fbuilderjQuery[ 'fbuilder' ] || {};
fbuilderjQuery[ 'fbuilder' ][ 'modules' ] = fbuilderjQuery[ 'fbuilder' ][ 'modules' ] || {};

fbuilderjQuery[ 'fbuilder' ][ 'modules' ][ 'energy' ] = {
	'tutorial' : 'https://cff.dwbooster.com/documentation#energy-operations-module',
	'toolbars'		: {
		'energy' : {
			'label' : 'Energy-Related Operations',
			'buttons' : [
                {
                    "value" : "CFFSOLARSAVINGS",
                    "code" : "CFFSOLARSAVINGS(",
                    "tip" : "<p><strong>CFFSOLARSAVINGS(args)</strong></p><p>Calculate solar panel savings with optimized performance. The args parameter is a plain object with the structure<br>{<br><b>monthlyConsumption:</b> Monthly electricity consumption in kWh,<br> <b>electricityRate:</b> Cost per kWh in local currency,<br> <b>solarSystemSize:</b> Solar system capacity in kW,<br> <b>solarHoursPerDay:</b> Average daily solar hours (default: 4.5),<br> <b>systemEfficiency:</b> System efficiency factor (default: 0.8),<br> <b>netMeteringRate:</b> Net metering compensation rate (default: same as electricity rate)<br>}<br><br>It returns an object containing a lot of useful information:<br> \
					{ <br>\
					  <b>production:</b> { <br>\
					    <b>dailyProduction:</b> Daily energy production, <br>\
					    <b>monthlyProduction:</b> Monthly energy production, <br>\
					    <b>annualProduction:</b> Annual energy production <br>\
					  }, <br>\
					  <b>consumption:</b> { <br>\
					    <b>monthlyConsumption:</b> Monthly consumption, <br>\
					    <b>annualConsumption:</b> Annual consumption <br>\
					  }, <br>\
					  <b>financial:</b> { <br>\
					    <b>monthlyElectricityBill:</b> Monthly electricity bill, <br>\
					    <b>annualElectricityBill:</b> Annual electricity bill, <br>\
					    <b>monthlySavings:</b> Monthly savings, <br>\
					    <b>annualSavings:</b> Annual savings, <br>\
					    <b>savingsPercentage:</b> Savings percentage, <br>\
					    <b>excessEnergyValue:</b> Excess energy value <br>\
				      }, <br>\
					  <b>metrics:</b> { <br>\
					    <b>productionToConsumptionRatio:</b> Production to consumption ratio, <br>\
					    <b>energyOffset:</b> Energy offset, <br>\
					    <b>excessEnergy:</b> Excess energy <br>\
				      } <br>\
			        }</p>"
                },
                {
                    "value" : "CFFPAYBACKPERIOD",
                    "code" : "CFFPAYBACKPERIOD(",
                    "tip" : "<p><strong>CFFPAYBACKPERIOD(args)</strong></p><p>Calculate payback period with NPV analysis. The args parameter is a plain object with the structure<br>{<br><b>initialInvestment:</b> Total upfront cost,<br> <b>annualSavings:</b> Annual energy cost savings,<br> <b>maintenanceCostPerYear:</b> Annual maintenance costs (default: 0),<br> <b>incentives:</b> Government incentives/rebates (default: 0),<br> <b>inflationRate:</b> Annual inflation rate (default: 0.03),<br> <b>degradationRate:</b> Annual system degradation (default: 0.005),<br> <b>analysisYears:</b> Years to analyze (default: 25),<br> <b>discountRate:</b> Discount rate (default: 0.05)<br>}<br><br>It returns an object containing a lot of useful information:<br>\
					{<br>\
						<b>financial:</b> { <br>\
							<b>initialInvestment:</b> Initial investment,<br>\
							<b>incentives:</b> Incentives,<br>\
							<b>netInitialInvestment:</b> Net initial investment,<br>\
							<b>simplePaybackPeriod:</b> Payback period,<br>\
							<b>discountedPaybackPeriod:</b> Discounted payback period,<br>\
							<b>netPresentValue:</b> Present value,<br>\
							<b>totalCashFlow:</b> Total cash flow<br>\
						},<br>\
						<b>analysis:</b> {<br>\
							<b>analysisYears:</b> Years,<br>\
							<b>inflationRate:</b> Inflation rate,<br>\
							<b>degradationRate:</b> Degradation rate,<br>\
							<b>discountRate:</b> Discount rate<br>\
						},<br>\
						<b>yearlyBreakdown:</b> Array with yearly analysis<br>\
					}</p>"
                },
                {
                    "value" : "CFFPOWERREQUIREMENTS",
                    "code" : "CFFPOWERREQUIREMENTS(",
                    "tip" : "<p><strong>CFFPOWERREQUIREMENTS(args)</strong></p><p>Calculate required power capacity for electrical installation. The args parameter is a plain object with the structure<br>{<br><b>appliances:</b> Array of appliance objects {name, power, hoursPerDay, quantity},<br> <b>simultaneityFactor:</b> Factor for simultaneous usage (default: 0.7),<br> <b>safetyFactor:</b> Safety margin factor (default: 1.2),<br> <b>powerFactor:</b> Power factor for AC systems (default: 0.9)<br>}<br><br>It returns an object containing a lot of useful information:<br>\
					{<br>\
						<b>summary:</b> {<br>\
							<b>totalConnectedLoad:</b> Total connected load,<br>\
							<b>peakDemand:</b> Peak demand,<br>\
							<b>diversifiedDemand:</b> Diversified demand,<br>\
							<b>requiredCapacityKW:</b> Required capacity in KW,<br>\
							<b>requiredCapacityKVA:</b> Required capacity in KVA,<br>\
							<b>recommendedBreakerSize:</b> Recommended breaker size<br>\
							<b>loadFactor:</b> Load factor<br>\
						},<br>\
						<b>consumption:</b> {<br>\
							<b>dailyConsumption:</b> Total daily consumption,<br>\
							<b>monthlyConsumption:</b> Monthly consumption,<br>\
							<b>annualConsumption:</b> Annual consumption<br>\
						},<br>\
						<b>factors:</b> {<br>\
							<b>simultaneityFactor:</b> Simultaneity factor,<br>\
							<b>safetyFactor:</b> Safety factor,<br>\
							<b>powerFactor:</b> Power factor<br>\
						},<br>\
						<b>appliances:</b> Appliance analysis,<br>\
						<b>hourlyDemand:</b> Demand<br>\
					}</p>"
                },
				{
                    "value" : "CFFENERGYEFFICIENCY",
                    "code" : "CFFENERGYEFFICIENCY(",
                    "tip" : "<p><strong>CFFENERGYEFFICIENCY(args)</strong></p><p>Calculate energy efficiency improvements and savings. The args is a plain object with the structure.<br>{<br><b>currentConsumption:</b> Current monthly consumption in kWh,<br> <b>electricityRate:</b> Cost per kWh,<br> <b>improvements:</b> Array of improvement objects {name, savingsPercentage, cost, lifespan}<br>}<br><br>It returns an object containing a lot of useful information:<br>\
					{<br>\
						<b>current:</b> {<br>\
							<b>monthlyConsumption:</b> Monthly consumption,<br>\
							<b>monthlyCost:</b> Monthly cost,<br>\
							<b>annualConsumption:</b> Annual consumption,<br>\
							<b>annualCost:</b> Annual cost<br>\
						},<br>\
						<b>improved:</b> {<br>\
							<b>monthlyConsumption:</b> Monthly consumption,<br>\
							<b>monthlyCost:</b> Monthly cost,<br>\
							<b>annualConsumption:</b> Annual consumption,<br>\
							<b>annualCost:</b> Annual cost<br>\
						},<br>\
						<b>savings:</b> {<br>\
							<b>monthlyEnergyReduction:</b> Monthly energy reduction,<br>\
							<b>annualEnergyReduction:</b> Annual energy reduction,<br>\
							<b>monthlyCostSavings:</b> Monthly cost savings,<br>\
							<b>annualCostSavings:</b> Annual cost savings,<br>\
							<b>totalSavingsPercentage:</b> Total savings percentage<br>\
						},<br>\
						<b>investment:</b> {<br>\
							<b>totalImplementationCost:</b> Total implementation cost,<br>\
							<b>overallPaybackPeriod:</b> Overall payback period<br>\
						},<br>\
						<b>improvements:</b> Improvement analysis<br>\
					}</p>"
                },
				{
                    "value" : "CFFBATTERYSTORAGE",
                    "code" : "CFFBATTERYSTORAGE(",
                    "tip" : "<p><strong>CFFBATTERYSTORAGE(args)</strong></p><p>Calculate battery storage requirements and economics. The args is a plain object with the structure.<br>{<br><b>dailyConsumption:</b> Daily energy consumption in kWh,<br> <b>autonomyDays:</b> Days of autonomy required (default: 1),<br> <b>batteryVoltage:</b> Battery system voltage (default: 48),<br> <b>depthOfDischarge:</b> Maximum depth of discharge (default: 0.8),<br> <b>batteryEfficiency:</b> Battery round-trip efficiency (default: 0.85),<br> <b>costPerKWh:</b> Battery cost per kWh (default: 400),<br> <b>cycleLife:</b> Battery cycle life (default: 5000)<br>}<br><br>It returns an object containing a lot of useful information:<br>\
					{<br>\
						<b>requirements:</b> {<br>\
							<b>dailyConsumption:</b> Daily consumption,<br>\
							<b>autonomyDays:</b> Autonomy days,<br>\
							<b>totalEnergyNeeded:</b> Total energy needed,<br>\
							<b>usableCapacityRequired:</b> Usable capacity required,<br>\
							<b>nominalCapacityRequired:</b> Nominal capacity required,<br>\
							<b>capacityAh:</b> Capacity Ah<br>\
						},<br>\
						<b>system:</b> {<br>\
							<b>batteryVoltage:</b> Battery voltage,<br>\
							<b>depthOfDischarge:</b> Depth of discharge,<br>\
							<b>batteryEfficiency:</b> Battery efficiency,<br>\
							<b>cycleLife:</b> Cycle life<br>\
						},<br>\
						<b>costs:</b> {<br>\
							<b>batteryCost:</b> Battery cost,<br>\
							<b>inverterCost:</b> Inverter cost,<br>\
							<b>bmsAndWiringCost:</b> BMS and wiring,<br>\
							<b>installationCost:</b> Installation cost,<br>\
							<b>totalSystemCost:</b> Total cost,<br>\
							<b>costPerKWh:</b> Cost per KWh,<br>\
							<b>costPerCycle:</b> Cost per cycle,<br>\
							<b>costPerKWhCycled:</b> Cost per KWh cycled<br>\
						},<br>\
						<b>lifecycle:</b> {<br>\
							<b>batteryLifeYears:</b> Battery life years,<br>\
							<b>cyclesPerYear:</b> Cycles per year,<br>\
							<b>replacementCost:</b> Battery cost,<br>\
							<b>maintenanceCostPerYear:</b> Maintenance cost per year<br>\
						}<br>\
					}</p>"
                },
				{
                    "value" : "CFFCONVERTENERGYUNITS",
                    "code" : "CFFCONVERTENERGYUNITS(",
                    "tip" : "<p><strong>CFFCONVERTENERGYUNITS(value, fromUnit, toUnit)</strong></p><p>Utility function to convert between energy units. The parameters are value to convert, fromUnit and toUnit are texts with any of units: kWh, MWh, Wh, BTU, J, kJ, MJ, cal, kcal, and therm.</p>"
                },
				{
                    "value" : "CFFENVIRONMENTALIMPACT",
                    "code" : "CFFENVIRONMENTALIMPACT(",
                    "tip" : "<p><strong>CFFENVIRONMENTALIMPACT(args)</strong></p><p>Calculate carbon footprint and environmental impact. The args is a plain object with the structure.<br>{<br><b>annualConsumption:</b> Annual energy consumption in kWh,<br> <b>gridCarbonIntensity:</b> Grid carbon intensity in kg CO2/kWh (default: 0.4),<br> <b>renewableEnergyRatio:</b> Ratio of renewable energy (0-1, default: 0)<br>}<br><br>It returns an object containing a lot of useful information:<br>\
					{<br>\
						<b>consumption:</b> {<br>\
							<b>annualConsumption:</b> Annual consumption,<br>\
							<b>gridEnergyConsumption:</b> Grid energy consumption,<br>\
							<b>renewableEnergyConsumption:</b> Renewable energy consumption,<br>\
							<b>renewableEnergyRatio:</b> Renewable energy ratio<br>\
						},<br>\
						<b>emissions:</b> {<br>\
							<b>gridCarbonIntensity:</b> Grid carbon intensity,<br>\
							<b>annualCarbonEmissions:</b> Annual carbon emissions,<br>\
							<b>carbonSaved:</b> Carbon saved,<br>\
							<b>monthlyCarbonEmissions:</b> Monthly carbon emissions<br>\
						},<br>\
						<b>equivalents:</b> {<br>\
							<b>treesEquivalent:</b> Trees equivalent,<br>\
							<b>carMilesEquivalent:</b> Car miles equivalent,<br>\
							<b>coalEquivalent:</b> Coal equivalent<br>\
						}<br>\
					}</p>"
                },
				{
                    "value" : "CFFDEMANDCHARGES",
                    "code" : "CFFDEMANDCHARGES(",
                    "tip" : "<p><strong>CFFDEMANDCHARGES(args)</strong></p><p>Calculate demand charges and time-of-use optimization. The args is a plain object with the structure.<br>{<br><b>hourlyUsage:</b> Array of 24 hourly usage values in kWh,<br> <b>rates:</b> Rate structure object,<br> <b>demandCharge:</b> Demand charge per kW (default: 0),<br> <b>timeOfUseRates:</b> Array of TOU rate objects {hours: [start, end], rate},<br> <b>baseRate:</b> Base energy rate if no TOU (default: 0.12)<br>}<br><br>It returns an object containing a lot of useful information:<br>\
					{<br>\
						<b>demand:</b> {<br>\
							<b>peakDemand:</b> Peak demand,<br>\
							<b>demandChargeRate:</b> Demand charge rate,<br>\
							<b>monthlyDemandCharge:</b> Monthly demand charge<br>\
						},<br>\
						<b>energy:</b> {<br>\
							<b>totalUsage:</b> Total usage,<br>\
							<b>totalEnergyCharge:</b> Total energy charge,<br>\
							<b>averageRate:</b> Average rate<br>\
						},<br>\
						<b>billing:</b> {<br>\
							<b>totalBill:</b> Total bill,<br>\
							<b>energyChargePercentage:</b> Energy charge percentage,<br>\
							<b>demandChargePercentage:</b> Demand charge percentage<br>\
						},<br>\
						<b>timeOfUse:</b> Time of use,<br>\
						<b>hourlyBreakdown:</b> Hourly charges<br>\
					}</p>"
                },
            ]
		}
	}
};