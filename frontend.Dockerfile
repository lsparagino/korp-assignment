# Build stage
FROM node:20-alpine AS build-stage
WORKDIR /app
COPY client/package*.json ./
RUN npm install --ignore-scripts
COPY client/ .
RUN npm run build

# Production stage
FROM nginx:stable-alpine
COPY --from=build-stage /app/dist /usr/share/nginx/html
COPY deployment/gcp/nginx-frontend.conf /etc/nginx/conf.d/default.conf
RUN chown -R nginx:nginx /usr/share/nginx/html \
    && chown -R nginx:nginx /var/cache/nginx \
    && chown -R nginx:nginx /var/log/nginx \
    && touch /var/run/nginx.pid \
    && chown nginx:nginx /var/run/nginx.pid
EXPOSE 8080
USER nginx
CMD ["nginx", "-g", "daemon off;"]
