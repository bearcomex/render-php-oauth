# Use official PHP image
FROM php:8.1-cli

# Set working directory
WORKDIR /app

# Copy all files into container
COPY . /app

# Expose port for Render
EXPOSE 10000

# Run PHP built-in server
CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
