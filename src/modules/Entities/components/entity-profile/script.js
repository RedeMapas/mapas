app.component('entity-profile', {
    template: $TEMPLATES['entity-profile'],

    props: {
        entity: {
            type: Entity,
            required: true
        },
        label: {
            type: Boolean,
            required: false,
            default: true
        }
    },
});
