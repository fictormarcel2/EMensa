FROM node:22-bookworm-slim AS build
WORKDIR /workspace/web
RUN npm install --global npm@11.6.2
COPY web/package.json web/package-lock.json ./
RUN npm ci
COPY web/ ./
RUN npm run build

FROM build AS checks
RUN npm test && npm audit --audit-level=moderate

FROM nginx:1.28-alpine AS runtime
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY --from=build /workspace/web/dist /usr/share/nginx/html
USER nginx
EXPOSE 8080
ENTRYPOINT ["nginx", "-g", "daemon off;"]
