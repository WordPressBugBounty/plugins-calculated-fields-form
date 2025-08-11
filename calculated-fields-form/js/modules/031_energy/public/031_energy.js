/*
 * energy.js v0.1
 * By: CALCULATED FIELD PROGRAMMERS
 * Includes energy-relate operations
 * Copyright 2025 CODEPEOPLE
 */
;
(function (root) {
    var lib = {};

    // Constants and utilities
    var CONSTANTS = {
        SOLAR_PANEL_EFFICIENCY_DEGRADATION: 0.005,
        AVERAGE_SOLAR_HOURS_PER_DAY: 4.5,
        DAYS_PER_YEAR: 365,
        MONTHS_PER_YEAR: 12,
        GRID_EFFICIENCY_LOSS: 0.08,
        BATTERY_EFFICIENCY: 0.85,
        INFLATION_RATE: 0.03,
        DEFAULT_DISCOUNT_RATE: 0.05,
        DEFAULT_POWER_FACTOR: 0.9,
        DEFAULT_GRID_CARBON_INTENSITY: 0.4
    };

	function _getConstants() {
		if( 'CFF_ENERGY_CONSTANTS' in window ) {
			try {
				for ( let i in CFF_ENERGY_CONSTANTS ) {
					if ( CFF_ENERGY_CONSTANTS.hasOwnProperty(i) ) {
						CONSTANTS[i] = CFF_ENERGY_CONSTANTS[i];
					}
				}
			} catch ( err ) { console.log(err); }
		}
		return CONSTANTS;
	};

    // Utility functions
    var utils = {
        round: function(value, decimals) {
            var factor = Math.pow(10, decimals || 2);
            return Math.round(value * factor) / factor;
        },

        validatePositive: function(value, name) {
            if (typeof value !== 'number' || value <= 0) {
                throw new Error(name + ' must be a positive number');
            }
        },

        validateArray: function(arr, name, expectedLength) {
            if (!Array.isArray(arr)) {
                throw new Error(name + ' must be an array');
            }
            if (expectedLength && arr.length !== expectedLength) {
                throw new Error(name + ' must have ' + expectedLength + ' elements');
            }
        },

        validateObject: function(obj, name) {
            if (!obj || typeof obj !== 'object') {
                throw new Error(name + ' object is required');
            }
        },

        calculateCompoundEffect: function(initial, rate, years) {
            return initial * Math.pow(1 + rate, years);
        },

        calculatePresentValue: function(futureValue, rate, years) {
            return futureValue / Math.pow(1 + rate, years);
        }
    };

    /**
     * Calculate solar panel savings with optimized performance
     */
    lib.CFFSOLARSAVINGS = lib.cffsolarsavings = function(params) {
		try {
			utils.validateObject(params, 'Parameters');

			let p = {
				monthlyConsumption: params.monthlyConsumption,
				electricityRate: params.electricityRate,
				solarSystemSize: params.solarSystemSize,
				solarHoursPerDay: params.solarHoursPerDay || _getConstants().AVERAGE_SOLAR_HOURS_PER_DAY,
				systemEfficiency: params.systemEfficiency || 0.8,
				netMeteringRate: params.netMeteringRate || params.electricityRate
			};

			utils.validatePositive(p.monthlyConsumption, 'Monthly consumption');
			utils.validatePositive(p.electricityRate, 'Electricity rate');
			utils.validatePositive(p.solarSystemSize, 'Solar system size');

			let dailyProduction = p.solarSystemSize * p.solarHoursPerDay * p.systemEfficiency,
				annualProduction = dailyProduction * _getConstants().DAYS_PER_YEAR,
				annualConsumption = p.monthlyConsumption * _getConstants().MONTHS_PER_YEAR,
				annualElectricityBill = annualConsumption * p.electricityRate,

				energyOffset = Math.min(annualProduction, annualConsumption),
				excessEnergy = Math.max(0, annualProduction - annualConsumption),
				annualSavings = (energyOffset * p.electricityRate) + (excessEnergy * p.netMeteringRate);

			return {
				production: {
					dailyProduction: utils.round(dailyProduction),
					monthlyProduction: utils.round(dailyProduction * _getConstants().DAYS_PER_YEAR / _getConstants().MONTHS_PER_YEAR),
					annualProduction: utils.round(annualProduction)
				},
				consumption: {
					monthlyConsumption: p.monthlyConsumption,
					annualConsumption: annualConsumption
				},
				financial: {
					monthlyElectricityBill: utils.round(annualElectricityBill / _getConstants().MONTHS_PER_YEAR),
					annualElectricityBill: utils.round(annualElectricityBill),
					monthlySavings: utils.round(annualSavings / _getConstants().MONTHS_PER_YEAR),
					annualSavings: utils.round(annualSavings),
					savingsPercentage: utils.round((annualSavings / annualElectricityBill) * 100),
					excessEnergyValue: utils.round(excessEnergy * p.netMeteringRate)
				},
				metrics: {
					productionToConsumptionRatio: utils.round(annualProduction / annualConsumption),
					energyOffset: utils.round(energyOffset),
					excessEnergy: utils.round(excessEnergy)
				}
			};
		} catch ( err ) { console.log( err ); }
    };

    /**
     * Calculate payback period with NPV analysis
     */
    lib.CFFPAYBACKPERIOD = function(params) {
		try {
			utils.validateObject(params, 'Parameters');

			var p = {
				initialInvestment: params.initialInvestment,
				annualSavings: params.annualSavings,
				maintenanceCostPerYear: params.maintenanceCostPerYear || 0,
				incentives: params.incentives || 0,
				inflationRate: params.inflationRate || _getConstants().INFLATION_RATE,
				degradationRate: params.degradationRate || _getConstants().SOLAR_PANEL_EFFICIENCY_DEGRADATION,
				analysisYears: params.analysisYears || 25,
				discountRate: params.discountRate || _getConstants().DEFAULT_DISCOUNT_RATE
			};

			utils.validatePositive(p.initialInvestment, 'Initial investment');
			utils.validatePositive(p.annualSavings, 'Annual savings');

			var netInitialInvestment = p.initialInvestment - p.incentives,
				cumulativeCashFlow = -netInitialInvestment,
				netPresentValue = -netInitialInvestment,
				paybackPeriod = null,

				// Pre-calculate factors for performance
				inflationFactors = [],
				degradationFactors = [],
				discountFactors = [];

			for (var year = 1; year <= p.analysisYears; year++) {
				inflationFactors[year] = Math.pow(1 + p.inflationRate, year - 1);
				degradationFactors[year] = Math.pow(1 - p.degradationRate, year - 1);
				discountFactors[year] = Math.pow(1 + p.discountRate, year);
			}

			var yearlyAnalysis = [];
			for (var year = 1; year <= p.analysisYears; year++) {
				var adjustedSavings = p.annualSavings * degradationFactors[year] * inflationFactors[year];
				var adjustedMaintenanceCost = p.maintenanceCostPerYear * inflationFactors[year];
				var netAnnualSavings = adjustedSavings - adjustedMaintenanceCost;

				cumulativeCashFlow += netAnnualSavings;
				var presentValue = netAnnualSavings / discountFactors[year];
				netPresentValue += presentValue;

				if (paybackPeriod === null && cumulativeCashFlow >= 0) {
					var previousCashFlow = year > 1 ? yearlyAnalysis[year - 2].cumulativeCashFlow : -netInitialInvestment;
					paybackPeriod = (year - 1) + Math.abs(previousCashFlow) / netAnnualSavings;
				}

				yearlyAnalysis.push({
					year: year,
					adjustedSavings: utils.round(adjustedSavings),
					maintenanceCost: utils.round(adjustedMaintenanceCost),
					netAnnualSavings: utils.round(netAnnualSavings),
					cumulativeCashFlow: utils.round(cumulativeCashFlow),
					presentValue: utils.round(presentValue)
				});
			}

			return {
				financial: {
					initialInvestment: p.initialInvestment,
					incentives: p.incentives,
					netInitialInvestment: utils.round(netInitialInvestment),
					simplePaybackPeriod: utils.round(netInitialInvestment / (p.annualSavings - p.maintenanceCostPerYear)),
					discountedPaybackPeriod: paybackPeriod ? utils.round(paybackPeriod) : null,
					netPresentValue: utils.round(netPresentValue),
					totalCashFlow: utils.round(cumulativeCashFlow)
				},
				analysis: {
					analysisYears: p.analysisYears,
					inflationRate: p.inflationRate,
					degradationRate: p.degradationRate,
					discountRate: p.discountRate
				},
				yearlyBreakdown: yearlyAnalysis
			};
		} catch ( err ) { console.log( err ); }
    };

    /**
     * Calculate power requirements with load analysis
     */
    lib.CFFPOWERREQUIREMENTS = function(params) {
		try {
			utils.validateObject(params, 'Parameters');
			utils.validateArray(params.appliances, 'Appliances');

			if (params.appliances.length === 0) {
				throw new Error('At least one appliance must be specified');
			}

			var p = {
					appliances: params.appliances,
					simultaneityFactor: params.simultaneityFactor || 0.7,
					safetyFactor: params.safetyFactor || 1.2,
					powerFactor: params.powerFactor || _getConstants().DEFAULT_POWER_FACTOR
				},

				totals = {
					connectedLoad: 0,
					dailyConsumption: 0
				},

				peakDemandByHour = new Array(24).fill(0),
				applianceAnalysis = [];

			// Process appliances efficiently
			p.appliances.forEach(function(appliance) {
				if (!appliance.name || typeof appliance.power !== 'number' || typeof appliance.hoursPerDay !== 'number') {
					throw new Error('Each appliance must have name, power, and hoursPerDay properties');
				}

				var quantity = appliance.quantity || 1,
					totalPower = appliance.power * quantity,
					dailyConsumption = totalPower * appliance.hoursPerDay / 1000,
					operatingHours = appliance.operatingHours || [9, 10, 11, 12, 13, 14, 15, 16];

				totals.connectedLoad += totalPower;
				totals.dailyConsumption += dailyConsumption;

				// Distribute load efficiently
				var powerPerHour = totalPower / operatingHours.length;
				operatingHours.forEach(function(hour) {
					if (hour >= 0 && hour < 24) {
						peakDemandByHour[hour] += powerPerHour;
					}
				});

				applianceAnalysis.push({
					name: appliance.name,
					quantity: quantity,
					unitPower: appliance.power,
					totalPower: totalPower,
					hoursPerDay: appliance.hoursPerDay,
					dailyConsumption: utils.round(dailyConsumption, 3),
					operatingHours: operatingHours
				});
			});

			var peakDemand = Math.max.apply(Math, peakDemandByHour),
				diversifiedDemand = peakDemand * p.simultaneityFactor,
				requiredCapacityKW = (diversifiedDemand / 1000) * p.safetyFactor,
				requiredCapacityKVA = requiredCapacityKW / p.powerFactor,

				averageLoad = totals.dailyConsumption * 1000 / 24,
				loadFactor = averageLoad / peakDemand;

			return {
				summary: {
					totalConnectedLoad: Math.round(totals.connectedLoad),
					peakDemand: Math.round(peakDemand),
					diversifiedDemand: Math.round(diversifiedDemand),
					requiredCapacityKW: utils.round(requiredCapacityKW),
					requiredCapacityKVA: utils.round(requiredCapacityKVA),
					recommendedBreakerSize: Math.ceil(requiredCapacityKW * 1.25 / 0.23),
					loadFactor: utils.round(loadFactor)
				},
				consumption: {
					dailyConsumption: utils.round(totals.dailyConsumption),
					monthlyConsumption: utils.round(totals.dailyConsumption * 30),
					annualConsumption: utils.round(totals.dailyConsumption * _getConstants().DAYS_PER_YEAR)
				},
				factors: {
					simultaneityFactor: p.simultaneityFactor,
					safetyFactor: p.safetyFactor,
					powerFactor: p.powerFactor
				},
				appliances: applianceAnalysis,
				hourlyDemand: peakDemandByHour.map(Math.round)
			};
		} catch ( err ) { console.log( err ); }
    };

    /**
     * Calculate energy efficiency improvements with compound effects
     */
    lib.CFFENERGYEFFICIENCY = function(params) {
		try {
			utils.validateObject(params, 'Parameters');

			var p = {
				currentConsumption: params.currentConsumption,
				electricityRate: params.electricityRate,
				improvements: params.improvements || []
			};

			utils.validatePositive(p.currentConsumption, 'Current consumption');
			utils.validatePositive(p.electricityRate, 'Electricity rate');

			var currentAnnualCost = p.currentConsumption * p.electricityRate * _getConstants().MONTHS_PER_YEAR,
				remainingConsumption = p.currentConsumption,
				totalImplementationCost = 0;

			var	improvementAnalysis = p.improvements.map(function(improvement) {
				if (!improvement.name || typeof improvement.savingsPercentage !== 'number') {
					throw new Error('Each improvement must have name and savingsPercentage properties');
				}

				var savingsFromImprovement = remainingConsumption * (improvement.savingsPercentage / 100);
				remainingConsumption -= savingsFromImprovement;

				var annualDollarSavings = savingsFromImprovement * p.electricityRate * _getConstants().MONTHS_PER_YEAR,
					implementationCost = improvement.cost || 0,
					lifespan = improvement.lifespan || 10;

				totalImplementationCost += implementationCost;

				var lifetimeSavings = annualDollarSavings * lifespan,
					netBenefit = lifetimeSavings - implementationCost;

				return {
					name: improvement.name,
					savingsPercentage: improvement.savingsPercentage,
					energySavingsKWh: utils.round(savingsFromImprovement * _getConstants().MONTHS_PER_YEAR),
					annualDollarSavings: utils.round(annualDollarSavings),
					implementationCost: implementationCost,
					simplePayback: implementationCost > 0 ? utils.round(implementationCost / annualDollarSavings) : 0,
					lifespan: lifespan,
					lifetimeSavings: utils.round(lifetimeSavings),
					netBenefit: utils.round(netBenefit),
					roi: implementationCost > 0 ? utils.round((netBenefit / implementationCost) * 100) : 0
				};
			});

			var newAnnualCost = remainingConsumption * p.electricityRate * _getConstants().MONTHS_PER_YEAR,
				totalAnnualSavings = currentAnnualCost - newAnnualCost,
				totalSavingsPercentage = ((p.currentConsumption - remainingConsumption) / p.currentConsumption) * 100;

			return {
				current: {
					monthlyConsumption: p.currentConsumption,
					monthlyCost: utils.round(currentAnnualCost / _getConstants().MONTHS_PER_YEAR),
					annualConsumption: utils.round(p.currentConsumption * _getConstants().MONTHS_PER_YEAR),
					annualCost: utils.round(currentAnnualCost)
				},
				improved: {
					monthlyConsumption: utils.round(remainingConsumption),
					monthlyCost: utils.round(newAnnualCost / _getConstants().MONTHS_PER_YEAR),
					annualConsumption: utils.round(remainingConsumption * _getConstants().MONTHS_PER_YEAR),
					annualCost: utils.round(newAnnualCost)
				},
				savings: {
					monthlyEnergyReduction: utils.round(p.currentConsumption - remainingConsumption),
					annualEnergyReduction: utils.round((p.currentConsumption - remainingConsumption) * _getConstants().MONTHS_PER_YEAR),
					monthlyCostSavings: utils.round(totalAnnualSavings / _getConstants().MONTHS_PER_YEAR),
					annualCostSavings: utils.round(totalAnnualSavings),
					totalSavingsPercentage: utils.round(totalSavingsPercentage)
				},
				investment: {
					totalImplementationCost: utils.round(totalImplementationCost),
					overallPaybackPeriod: totalImplementationCost > 0 ? utils.round(totalImplementationCost / totalAnnualSavings) : 0
				},
				improvements: improvementAnalysis
			};
		} catch ( err ) { console.log( err ); }
    };

    /**
     * Calculate battery storage requirements and economics
     */
    lib.CFFBATTERYSTORAGE = function(params) {
		try {
			utils.validateObject(params, 'Parameters');

			var p = {
				dailyConsumption: params.dailyConsumption,
				autonomyDays: params.autonomyDays || 1,
				batteryVoltage: params.batteryVoltage || 48,
				depthOfDischarge: params.depthOfDischarge || 0.8,
				batteryEfficiency: params.batteryEfficiency || _getConstants().BATTERY_EFFICIENCY,
				costPerKWh: params.costPerKWh || 400,
				cycleLife: params.cycleLife || 5000
			};

			utils.validatePositive(p.dailyConsumption, 'Daily consumption');

			var totalEnergyNeeded = p.dailyConsumption * p.autonomyDays,
				usableCapacityRequired = totalEnergyNeeded / p.batteryEfficiency,
				nominalCapacityRequired = usableCapacityRequired / p.depthOfDischarge,
				capacityAh = (nominalCapacityRequired * 1000) / p.batteryVoltage,

				// Cost calculations
				costs = {
					battery: nominalCapacityRequired * p.costPerKWh,
					inverter: nominalCapacityRequired * 200,
					bmsAndWiring: nominalCapacityRequired * 100
				};
			costs.installation = costs.battery * 0.2;
			costs.total = costs.battery + costs.inverter + costs.bmsAndWiring + costs.installation;

			// Lifecycle calculations
			var cyclesPerYear = _getConstants().DAYS_PER_YEAR,
				batteryLifeYears = p.cycleLife / cyclesPerYear,
				costPerCycle = costs.battery / p.cycleLife,
				costPerKWhCycled = costPerCycle / (nominalCapacityRequired * p.depthOfDischarge);

			return {
				requirements: {
					dailyConsumption: p.dailyConsumption,
					autonomyDays: p.autonomyDays,
					totalEnergyNeeded: utils.round(totalEnergyNeeded),
					usableCapacityRequired: utils.round(usableCapacityRequired),
					nominalCapacityRequired: utils.round(nominalCapacityRequired),
					capacityAh: utils.round(capacityAh)
				},
				system: {
					batteryVoltage: p.batteryVoltage,
					depthOfDischarge: p.depthOfDischarge,
					batteryEfficiency: p.batteryEfficiency,
					cycleLife: p.cycleLife
				},
				costs: {
					batteryCost: utils.round(costs.battery),
					inverterCost: utils.round(costs.inverter),
					bmsAndWiringCost: utils.round(costs.bmsAndWiring),
					installationCost: utils.round(costs.installation),
					totalSystemCost: utils.round(costs.total),
					costPerKWh: p.costPerKWh,
					costPerCycle: utils.round(costPerCycle),
					costPerKWhCycled: utils.round(costPerKWhCycled)
				},
				lifecycle: {
					batteryLifeYears: utils.round(batteryLifeYears),
					cyclesPerYear: cyclesPerYear,
					replacementCost: utils.round(costs.battery),
					maintenanceCostPerYear: utils.round(costs.total * 0.02)
				}
			};
		} catch ( err ) { console.log( err ); }
    };

    /**
     * Optimized energy unit conversion with lookup table
     */
    lib.CFFCONVERTENERGYUNITS = (function() {
        var conversionToKWh = {
            'kWh': 1, 'MWh': 1000, 'Wh': 0.001, 'BTU': 0.000293071,
            'J': 2.77778e-7, 'kJ': 0.000277778, 'MJ': 0.277778,
            'cal': 1.16279e-6, 'kcal': 0.00116279, 'therm': 29.3001
        };

        return function(value, fromUnit, toUnit) {
			try {
				if (typeof value !== 'number' || !conversionToKWh[fromUnit] || !conversionToKWh[toUnit]) {
					throw new Error('Invalid parameters for energy unit conversion');
				}

				return utils.round((value * conversionToKWh[fromUnit]) / conversionToKWh[toUnit], 6);
			} catch ( err ) { console.log( err ); }
        };
    })();

    /**
     * Calculate environmental impact with carbon footprint
     */
    lib.CFFENVIRONMENTALIMPACT = function(params) {
		try {
			utils.validateObject(params, 'Parameters');

			var p = {
				annualConsumption: params.annualConsumption,
				gridCarbonIntensity: params.gridCarbonIntensity || _getConstants().DEFAULT_GRID_CARBON_INTENSITY,
				renewableEnergyRatio: params.renewableEnergyRatio || 0
			};

			utils.validatePositive(p.annualConsumption, 'Annual consumption');

			if (p.renewableEnergyRatio < 0 || p.renewableEnergyRatio > 1) {
				throw new Error('Renewable energy ratio must be between 0 and 1');
			}

			var gridEnergyConsumption = p.annualConsumption * (1 - p.renewableEnergyRatio),
				renewableEnergyConsumption = p.annualConsumption * p.renewableEnergyRatio,
				annualCarbonEmissions = gridEnergyConsumption * p.gridCarbonIntensity,
				carbonSaved = renewableEnergyConsumption * p.gridCarbonIntensity;

			return {
				consumption: {
					annualConsumption: p.annualConsumption,
					gridEnergyConsumption: utils.round(gridEnergyConsumption),
					renewableEnergyConsumption: utils.round(renewableEnergyConsumption),
					renewableEnergyRatio: p.renewableEnergyRatio
				},
				emissions: {
					gridCarbonIntensity: p.gridCarbonIntensity,
					annualCarbonEmissions: utils.round(annualCarbonEmissions),
					carbonSaved: utils.round(carbonSaved),
					monthlyCarbonEmissions: utils.round(annualCarbonEmissions / 12)
				},
				equivalents: {
					treesEquivalent: utils.round(annualCarbonEmissions / 21.77),
					carMilesEquivalent: utils.round(annualCarbonEmissions / 0.404),
					coalEquivalent: utils.round(annualCarbonEmissions / 2.23)
				}
			};
		} catch ( err ) { console.log( err ); }
    };

    /**
     * Calculate demand charges and TOU optimization
     */
    lib.CFFDEMANDCHARGES = function(params) {
		try {
			utils.validateObject(params, 'Parameters');
			utils.validateArray(params.hourlyUsage, 'Hourly usage', 24);

			var p = {
				hourlyUsage: params.hourlyUsage,
				rates: params.rates || {},
				demandCharge: (params.rates && params.rates.demandCharge) || 0,
				timeOfUseRates: (params.rates && params.rates.timeOfUseRates) || [],
				baseRate: (params.rates && params.rates.baseRate) || 0.12
			};

			var peakDemand = Math.max.apply(Math, p.hourlyUsage),
				monthlyDemandCharge = peakDemand * p.demandCharge,
				totalEnergyCharge = 0,
				touBreakdown = {};

			var hourlyCharges = p.hourlyUsage.map(function(usage, hour) {
				var rate = p.baseRate,
					ratePeriod = 'Base';

				// Find applicable TOU rate efficiently
				for (var i = 0; i < p.timeOfUseRates.length; i++) {
					var touRate = p.timeOfUseRates[i],
						startHour = touRate.hours[0],
						endHour = touRate.hours[1],

						isInPeriod = startHour <= endHour ?
						(hour >= startHour && hour < endHour) :
						(hour >= startHour || hour < endHour);

					if (isInPeriod) {
						rate = touRate.rate;
						ratePeriod = touRate.name || ('TOU-' + i);
						break;
					}
				}

				var hourlyCharge = usage * rate;
				totalEnergyCharge += hourlyCharge;

				// Accumulate TOU breakdown
				if (!touBreakdown[ratePeriod]) {
					touBreakdown[ratePeriod] = { usage: 0, charge: 0, rate: rate };
				}
				touBreakdown[ratePeriod].usage += usage;
				touBreakdown[ratePeriod].charge += hourlyCharge;

				return {
					hour: hour,
					usage: utils.round(usage, 3),
					rate: rate,
					ratePeriod: ratePeriod,
					charge: utils.round(hourlyCharge)
				};
			});

			// Round TOU breakdown
			Object.keys(touBreakdown).forEach(function(period) {
				touBreakdown[period].usage = utils.round(touBreakdown[period].usage, 3);
				touBreakdown[period].charge = utils.round(touBreakdown[period].charge);
			});

			var totalBill = totalEnergyCharge + monthlyDemandCharge,
				totalUsage = p.hourlyUsage.reduce(function(sum, usage) { return sum + usage; }, 0);

			return {
				demand: {
					peakDemand: utils.round(peakDemand, 3),
					demandChargeRate: p.demandCharge,
					monthlyDemandCharge: utils.round(monthlyDemandCharge)
				},
				energy: {
					totalUsage: utils.round(totalUsage, 3),
					totalEnergyCharge: utils.round(totalEnergyCharge),
					averageRate: utils.round(totalUsage > 0 ? totalBill / totalUsage : 0, 4)
				},
				billing: {
					totalBill: utils.round(totalBill),
					energyChargePercentage: utils.round((totalEnergyCharge / totalBill) * 100),
					demandChargePercentage: utils.round((monthlyDemandCharge / totalBill) * 100)
				},
				timeOfUse: touBreakdown,
				hourlyBreakdown: hourlyCharges
			};
		} catch ( err ) { console.log( err ); }
    };

    // Export the library
    root.CF_ENERGY = lib;

})(this);
