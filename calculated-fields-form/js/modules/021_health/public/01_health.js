/*
* health.js v0.1
* By: CALCULATED FIELD PROGRAMMERS
* Health related operations
* Copyright 2025 CODEPEOPLE
*/

;(function(root){
	var lib = {};


	/*** PRIVATE FUNCTIONS ***/
	function _cm_in(v){ return v/2.54; }
	function _in_cm(v){ return v*2.54; }

	function _lb_kg(v){ return v/2.2; }
	function _kg_lb(v){ return v*2.2; }

	/*** PUBLIC FUNCTIONS ***/

	lib.cf_health_version = '0.1';

	// gender: male/female, height, unit: metric/imperial
	lib.IDEALWEIGHTCALCULATOR = lib.idealweightcalculator = function( gender, height, unit ){
		unit  = (unit || 'metric').toLowerCase();
		height	 = unit == 'imperial' ? _in_cm(height) : height;
		let output = (height - 100) * (gender.toLowerCase() == 'male' ? 0.9 : 0.85);
		return unit == 'imperial' ? _kg_lb(output) : output;
	};

	// height, weight, unit: metric/imperial, as_text: true/false
	lib.BMICALCULATOR = lib.bmicalculator = function( height, weight, unit, as_text ){
		unit = (unit || 'metric').toLowerCase();
		as_text = as_text || false;

		height = (unit == 'imperial' ? _in_cm(height) : height)/100;
		weight = unit == 'imperial' ? _lb_kg(weight) : weight;

		let bmi = PREC(weight/Math.pow(height,2),2,true);

		if( ! as_text ) return bmi;

		if(bmi < 18.5) return 'Underweight';
        if(bmi <= 24.9) return 'Normal';
        if(bmi <= 29.9) return 'Overweight';
        return 'Obese';
	};

	// age, gender:male/female, height, weight, unit: metric/imperial, activity: sedentary/light/moderate/active/very/extra
	lib.BMRCALCULATOR= lib.bmrcalculator = function( age, gender, height, weight, unit ){
		unit = (unit || 'metric').toLowerCase();
		height = unit == 'imperial' ? _in_cm(height) : height;
		weight = unit == 'imperial' ? _lb_kg(weight) : weight;

		return 10*weight + 6.25*height - 5*age + (gender.toLowerCase() == 'male' ? 5 : -161);
	};

	// age, gender:male/female, height, weight, unit: metric/imperial, activity: sedentary/light/moderate/active/very/extra
	lib.MAINTENANCECALORIESCALCULATOR = lib.maintenancecaloriescalculator = function( age, gender, height, weight, unit, activity ){
		unit = (unit || 'metric').toLowerCase();
		activity = (activity || 'moderate').toLowerCase();

		height = unit == 'imperial' ? _in_cm(height) : height;
		weight = unit == 'imperial' ? _lb_kg(weight) : weight;

		let factors = {'sedentary': 1.2, 'light': 1.375, 'moderate': 1.465, 'active': 1.55, 'very': 1.725, 'extra': 1.9}
		let factor = activity in factors ? factors[activity] : 1.465;


		let bmr = lib.BMRCALCULATOR(age, gender.toLowerCase(), height, weight, 'metric');

		return Math.round(bmr*factor);
	};

	// age, gender:male/female, height, weight, unit: metric/imperial, activity: sedentary/light/moderate/active/very/extra,
	// goal: lose/gain/maintain, details: true/false
	lib.BYGOALCALORIESCALCULATOR = lib.bygoalcaloriescalculator = function( age, gender, height, weight, unit, activity, goal, details ){
		unit = (unit || 'metric').toLowerCase();
		activity = (activity || 'moderate').toLowerCase();
		goal = (goal || 'maintain').toLowerCase();
		details = details || false;

		height = unit == 'imperial' ? _in_cm(height) : height;
		weight = unit == 'imperial' ? _lb_kg(weight) : weight;


		let goals = {'lose': -500, 'gain': 500, 'maintain': 0};
		let goal_factor = goal in goals ? goals[goal] : 0;

		let calories = lib.MAINTENANCECALORIESCALCULATOR(age, gender, height, weight, unit, activity) + goal_factor;
		if( ! details ) return calories;

		let proteins = weight*2.2;
		let fat = calories * 0.25 / 9;
		let carbs = (calories*1 - (proteins * 4 + fat * 9)) / 4;

		return {
			'calories': calories,
			'proteins': Math.round(proteins),
			'fat': Math.round(fat),
			'carbs': Math.round(carbs)
		};
	};

	// age, gender:male/female, weight, unit: kg/lb, activity: sedentary/light/moderate/very/extra,
	// climate: cold/mild/warm/hot
	lib.WATERINTAKECALCULATOR = lib.waterintakecalculator = function( age, gender, weight, unit, activity, climate ){
		unit = (unit || 'kg').toLowerCase();
		activity = (activity || 'moderate').toLowerCase();
		weight = unit == 'lb' || unit == 'imperial' ? _lb_kg(weight) : weight;
		climate = climate || 'mild';

		const activityMultipliers = {'sedentary': 1.0,'light': 1.2,'moderate': 1.4,'very': 1.6,'extra': 1.8};
		let activity_factor = activity in activityMultipliers ? activityMultipliers[activity] : 1.4;

		const climateMultipliers = {'cold': 0.9,'mild': 1.0,'warm': 1.15,'hot': 1.3};
		let climate_factor = climate in climateMultipliers ? climateMultipliers[climate] : 1;

		let baseIntake = weight * 0.033;
		let ageMultiplier = 1.0;
		if (age < 18) ageMultiplier = 1.1;
		else if (age > 65) ageMultiplier = 1.05;

		let genderMultiplier = gender.toLowerCase() === 'male' ? 1.0 : 0.9;

		let waterIntake = baseIntake *
			ageMultiplier *
			genderMultiplier *
			activity_factor *
			climate_factor;

		waterIntake = Math.max(1.5, waterIntake);
		waterIntake = Math.min(5.0, waterIntake);

		return PREC(waterIntake, 2, true);
	};

	// gender:male/female, hip, waist
	lib.HIPTOWAISTRATIOCALCULATOR = lib.hiptowaistratiocalculator = function( gender, hip, waist ){
		gender = gender.toLowerCase();

		let ratio = waist / hip;
		let shape = ratio <= 0.9 ? 'pear' : (ratio <=1 ? 'avocado' : 'apple');
		let ideal = gender == 'male' ? '0.9-1.0' : '0.7-0.8';
		let risk;

		if ( 'male' == gender ) {
			if (ratio <= 0.95) risk = 'Low health risk';
			else if (ratio <= 1) risk = 'Moderate health risk';
			else risk = 'High health risk';
		} else {
			if (ratio <= 0.8) risk = 'Low health risk';
			else if (ratio <= 0.84) risk = 'Moderate health risk';
			else risk = 'High health risk';
		}

		return {
			'ratio': PREC(ratio,2,true),
			'shape': shape,
			'risk' : risk,
			'ideal': ideal
		};
	};

	root.CF_HEALTH = lib;

})(this);