const fetch: (url: RequestInfo, init?: RequestInit) => Promise<Response> = require("node-fetch");
interface IResource {
    page: string;
    key: string;
    value: string;
}
export class ServerStorage{
    private subPage: string;
    private headers: Record<string, string>;
    private apiRoot = "https://matquiz.dk/ServerStorage/storageAPI.php";

    /**
     * @param subPage an identifier for your page.
     * @param authToken the authentication token that is needed to get access to keyv value store. 
     * Ask Lukas for this.
     */
    constructor(subPage: string, authToken: string){
        this.subPage = subPage;
        this.headers = {
            "Content-Type": "application/json",
            "Authorization": authToken
        };
    }

    /**
     * Removes an item corresponding to page.
     * @param key of item to be removed
     */
    async removeItem(key: string): Promise<void> {
        const body: Record<string, string> = {
            page: this.subPage,
            key: key
        };
        let response = await fetch(this.apiRoot,{
            method: "DELETE",
            headers: this.headers,
            body: JSON.stringify(body)
        })
        if (response.status === 204) return;
        else if (response.status === 404) console.log("No key to be deleted matched " + key);
        else throw new Error("Unknown error: " + response.status);
    }

    /**
     * Retrives item
     * @param key key of value to be retrieved
     * @returns value
     * @throws No such key found.
     */
    async getItem(key: string): Promise<string> {
        let qs = `key=${key}&page=${this.subPage}`;
        let response = await fetch(`${this.apiRoot}?${qs}`,{
            method: "GET",
            headers: this.headers,
        });        
        let res: string = await response.text();
        const errorResponse = res === "Error getting value" || response.status === 404;
        if (errorResponse) {
            throw new Error("No such key found")
        }
        let resource = JSON.parse(res) as IResource;
        return resource.value;
    }
    
    /**
     * Adds item to key value store with given page.
     * @param key 
     * @param value 
     */
    async addItem(key: string, value: string): Promise<void> {
        let body: Record<string, string> = {
            page: this.subPage,
            key: key,
            value: value
        }
        let response =  await fetch(this.apiRoot,{
            method: "POST",
            headers: this.headers,
            body: JSON.stringify(body)
        })
        if (!response.ok) {
            throw new Error("Uknown error on upload: " + response.status)
        }
    }
    /**
     * Clears entire key value store on this page
     */
    async clear(): Promise<void> {
        let response = await fetch(this.apiRoot,{
            method: "DELETE",
            headers: this.headers,
            body: JSON.stringify({page: this.subPage})
        })
        if (response.status === 204) return;
        else throw new Error("Unknown error: " + response.status);
    }
}