import functools
import http.server
import ssl

HOST = "0.0.0.0"
PORT = 443
ROOT = "C:/wamp64/www"
CERT = "C:/wamp64/bin/apache/apache2.4.59/conf/ssl/localhost.crt"
KEY = "C:/wamp64/bin/apache/apache2.4.59/conf/ssl/localhost.key"

handler = functools.partial(http.server.SimpleHTTPRequestHandler, directory=ROOT)

httpd = http.server.ThreadingHTTPServer((HOST, PORT), handler)
context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
context.load_cert_chain(certfile=CERT, keyfile=KEY)
httpd.socket = context.wrap_socket(httpd.socket, server_side=True)
httpd.serve_forever()
