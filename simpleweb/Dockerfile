# Specify a base image
FROM alpine:latest

# Install Node.js and npm
RUN apk add --no-cache nodejs npm

# Set the working directory
WORKDIR /usr/app

# Copy package.json and package-lock.json (if you have it)
COPY package*.json ./

# Install dependencies
RUN npm install --production  # Install only production dependencies

# Copy the rest of the application code
COPY . .

# Expose the application port
EXPOSE 8080

# Default command
CMD ["npm", "start"]
