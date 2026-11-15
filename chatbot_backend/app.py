
from flask import Flask, request, jsonify
from flask_cors import CORS

from chatbot import get_chatbot_reply
from config import Config

app = Flask(__name__)
app.config.from_object(Config)

# Local development: allow frontend to call Flask API
CORS(app, resources={r"/api/*": {"origins": "*"}})


@app.route("/api/health", methods=["GET"])
def health():
    return jsonify({
        "success": True,
        "message": "DAAES Chatbot Backend is running!"
    })


@app.route("/api/chat", methods=["POST"])
def chat():
    try:
        data = request.get_json(silent=True) or {}
        message = data.get("message", "")

        if not isinstance(message, str) or not message.strip():
            return jsonify({
                "success": False,
                "reply": "Please enter a message."
            }), 400

        if len(message) > 1000:
            return jsonify({
                "success": False,
                "reply": "Your message is too long. Please keep it under 1000 characters."
            }), 400

        reply = get_chatbot_reply(message.strip())

        return jsonify({
            "success": True,
            "reply": reply
        })

    except Exception as error:
        print("Chatbot Error:", error)

        return jsonify({
            "success": False,
            "reply": "Sorry, something went wrong. Please try again."
        }), 500


if __name__ == "__main__":
    app.run(
        host="127.0.0.1",
        port=5000,
        debug=True
    )
