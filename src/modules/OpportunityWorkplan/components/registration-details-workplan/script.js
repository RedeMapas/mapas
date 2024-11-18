app.component('registration-details-workplan', {
    template: $TEMPLATES['registration-details-workplan'],
    setup() {
        const text = Utils.getTexts('registration-details-workplan');
        return { text };
    },
    props: {
        registration: {
            type: Entity,
            required: true
        },
    },
    data() {
        this.getWorkplan();

        const entityWorkplan = new Entity('workplan');

        return {
            workplan: entityWorkplan,
        };
    },
    methods: {
        getWorkplan() {
            const api = new API('workplan');
            
            const response = api.GET(`${this.registration.id}`);
            response.then((res) => res.json().then((data) => {
                if (data.workplan != null) {
                    this.workplan = data.workplan;
                }
            }));
        },
        convertToCurrency(field) {
            return new Intl.NumberFormat("pt-BR", {
                style: "currency",
                currency: "BRL"
              }).format(field);
        }
    },
})