# Bắt đầu file api.py
from flask import Flask, request, jsonify
from flask_cors import CORS # Import CORS
from model import predict_intent, train_model

app = Flask(__name__)
CORS(app)

# Endpoint dự đoán intent
@app.route('/get_intent', methods=['POST'])
def get_intent():
    data = request.get_json()
    if not data or 'message' not in data:
        return jsonify({'error': 'Invalid input'}), 400

    user_message = data['message']
    intent = predict_intent(user_message)
    return jsonify({'intent': intent})

# Endpoint mới để kích hoạt việc huấn luyện lại
@app.route('/retrain', methods=['POST'])
def retrain():
    try:
        train_model()
        return jsonify({'status': 'success', 'message': 'Model trained successfully'})
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)}), 500

# Chạy server
if __name__ == '__main__':
    # Huấn luyện model ngay khi server khởi động
    train_model()
    app.run(host='127.0.0.1', port=5000, debug=True)