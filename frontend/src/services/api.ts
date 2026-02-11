import axios from "axios";
import { startLoading, stopLoading } from "../utils/globalLoader";
import { apiBaseUrl } from "./site";

const baseURL = apiBaseUrl();

const api = axios.create({
  baseURL,
  timeout: 10000,
  withCredentials: true,
});

api.interceptors.request.use((config) => {
  startLoading();
  return config;
});

api.interceptors.response.use(
  (response) => {
    stopLoading();
    return response;
  },
  (error) => {
    stopLoading();
    return Promise.reject(error);
  },
);

export default api;
