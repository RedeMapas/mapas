app.component("panel--seal-relations-tabs", {
	template: $TEMPLATES["panel--seal-relations-tabs"],
	emits: [],

	setup(props, { slots }) {
		const hasSlot = (name) => !!slots[name];
		return { hasSlot };
	},

	created() {},

	data() {
		const today = new Date(); // Current date and time

		// Parse the date string in the format yyyy-mm-dd
		function parseDate(dateString) {
			const [day, month, year] = dateString.split("/").map(Number);
			return new Date(year, month - 1, day); // month is 0-based in Date
		}

		// Filter valid seals where validateDate is greater than or equal to today
		let validSeals = this.entity.seals.filter((seal) => {
			const validateDate = parseDate(
				seal.sealRelationFullData.validateDate
			);
			return (
				validateDate >= today ||
				!seal.sealRelationFullData.seal.validPeriod
			);
		});

		// Filter expired seals where validateDate is less than today
		let expiredSeals = this.entity.seals.filter((seal) => {
			const validateDate = parseDate(
				seal.sealRelationFullData.validateDate
			);
			return (
				validateDate < today &&
				seal.sealRelationFullData.seal.validPeriod > 0
			);
		});

		let validSealsLabel = `Válidos (${validSeals.length})`;
		let expiredSealsLabel = `Expirados (${expiredSeals.length})`;

		return { validSeals, expiredSeals, validSealsLabel, expiredSealsLabel };
	},

	computed: {},

	props: {
		entity: {
			type: Entity,
			required: true,
		},
	},

	methods: {
		formatReceivedDate(seal) {
			const timestamp = seal.createTimestamp.date;
			let [date, time] = timestamp.split(" ");
			let [year, month, day] = date.split("-");
			return `${day}/${month}/${year}`;
		},

		formatValidDate(seal) {
			const date = seal.sealRelationFullData.validateDate;
			const validPeriod = seal.sealRelationFullData.seal.validPeriod;
			const outputMsg =
				validPeriod > 0
					? validPeriod === 1
						? `${validPeriod} mês (até ${date})`
						: `${validPeriod} meses (até ${date})`
					: "Não expira";
			return outputMsg;
		},
        removeSeal(seal) {
            // this.entity.removeSealRelation(seal);
			this.entity.seals = this.entity.seals.filter((el) => seal != el);
        }
	},
});
