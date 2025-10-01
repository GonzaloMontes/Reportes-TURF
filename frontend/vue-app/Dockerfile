# Imagen base de Node
FROM node:20-alpine

# Carpeta de trabajo
WORKDIR /app

# Copiar package.json y lock
COPY package*.json ./

# Instalar dependencias
RUN npm install

# Copiar el resto del código
COPY . .

# Puerto de Vite
EXPOSE 5173

# Comando por defecto (modo dev con hot reload)
CMD ["npm", "run", "dev", "--", "--host"]

