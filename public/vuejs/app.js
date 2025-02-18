/**
 * The Real Estate App Main Vue Js
 */

const Login = Vue.defineAsyncComponent(() => {
    return axios.get('http://localhost/realestate/')
                .then(response => ({
                    template: response.data,
                    methods: {
                        onVueClick(){
                            console.log('Vue is here too');
                        }
                    }
                }))
                .catch(error => console.error('Error retrieving login', error));
});
const Upload = Vue.defineAsyncComponent(() => {
    return axios.get('http://localhost/realestate/startupload')
                .then(response => ({template: response.data}))
                .catch(error => console.error('Error retrieving upload', error));
});
const Analysis = Vue.defineAsyncComponent(() => {
    return axios.get('http://localhost/realestate/analyze')
                .then(response => ({template: response.data}))
                .catch(error => console.error('Error retireving analysis', error));
});

const realestateapp = Vue.createApp({
    data(){
        return {
            currentView: "login"
        };
    },
    components: {
        login: Login,
        upload: Upload,
        analysis: Analysis
    }
});

realestateapp.mount('#real-estate-app');
