app.component('registration-actions', {
    template: $TEMPLATES['registration-actions'],

    props: {
        registration: {
            type: Entity,
            required: true
        },
    },
    
    methods: {
        async send() {
            const data = {id: this.registration.id};
            if (this.registration.category) {
                data.category = this.registration.category;
            }
            
            try {
                this.registration.disableMessages();
                await this.save();
                this.registration.enableMessages();

                await this.registration.POST('send', {data}).then((response) => {
                    console.log(response);
                });
            } catch(error) {
                console.log(error);
            }
        },
        async save() {
            return this.registration.save(false);
        },
        exit() {
            this.registration.save().then(() => {
                if (window.history.length > 2) {
                    window.history.back();
                } else {
                    window.location.href = $MAPAS.baseURL+'panel';
                }
            });
        },
    },
});