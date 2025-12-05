from flask import Flask, request, jsonify
from flask_cors import CORS
from model import predict_intent, train_model

app = Flask(__name__)
CORS(app)

@app.route('/get_intent', methods=['POST'])
def get_intent():
    data = request.get_json()
    if not data or 'message' not in data:
        return jsonify({'error': 'Invalid input'}), 400

    predicted_label = predict_intent(data['message'])
    
    response = {'intent': predicted_label, 'is_fallback': False}
    if predicted_label == "#KHONG_HIEU":
        response['intent'] = None
        response['is_fallback'] = True

    return jsonify(response)

@app.route('/retrain', methods=['POST'])
def retrain():
    try:
        train_model()
        return jsonify({'status': 'success'})
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)}), 500

if __name__ == '__main__':
    train_model()
    app.run(host='127.0.0.1', port=5000, debug=True)